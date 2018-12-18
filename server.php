<?php
error_reporting(7);
set_time_limit(0);
date_default_timezone_set('Asia/shanghai');

/*
*
*	reference：http://www.php.net/manual/zh/ref.sockets.php
*/

class WebSocket {
    const LISTEN_SOCKET_NUM = 200;		// 最大连接数
    private $sockets = [];				// sockets列表
    private $master;					// 主机
	
	//初始化...
    public function __construct($host, $port) {
		// 主机IP地址
		$host = "192.168.1.211";
		// 创建socket,IPV4,TCP
		$this->master = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
		// 设置IP和端口重用
		socket_set_option($this->master, SOL_SOCKET, SO_REUSEADDR, 1);
		// 将IP和端口绑定到主机socket;
		socket_bind($this->master, $host, $port);
		// 监听
		socket_listen($this->master, self::LISTEN_SOCKET_NUM);
		// 加入sockets列表 
        $this->sockets[0] = ['resource' => $this->master];
		// 
		echo $host;
        while (true) { $this->runServer(); }
    }
	
	//运行中...
    private function runServer() {
        $write = $except = NULL;
        $sockets = array_column($this->sockets, 'resource');
        $read_num = socket_select($sockets, $write, $except, NULL);
        
        foreach ($sockets as $socket) {
            if ($socket == $this->master) {		//当前主机
                $client = socket_accept($this->master);
                if ($client != false) { self::connect($client); }
            } 
			else {								//已建立客户端链接
                $bytes = @socket_recv($socket, $buffer, 2048, 0);
				//disconnect
				if ($bytes < 9) { $recv_msg = $this->disconnect($socket); }
				else {
                    if (!($this->sockets[(int)$socket]['handshake'])) {	//如果没有“握手”
                        self::handShake($socket, $buffer);
                        continue;
                    }
					else {
						$recv_msg = self::parse($buffer); 	//处理消息
					}
                }
                array_unshift($recv_msg, 'receive_msg');
                $msg = self::dealMsg($socket, $recv_msg);

                $this->broadcast($msg);
            }
        }
    }

    /**
     * 将socket添加到已连接列表,但握手状态留空;
     *
     * @param $socket
     */
    public function connect($socket) {
        socket_getpeername($socket, $ip, $port);
        $socket_info = [
            'resource' => $socket,	//客户端 socket
            'uname' => 'Anonymous',	//用户名
            'handshake' => false,	//是否加入
            'ip' => $ip,			//ip
            'port' => $port,		//端口
        ];
        $this->sockets[(int)$socket] = $socket_info;
    }

    /**
     * 客户端关闭连接，注销
     *
     * @param $socket
     *
     * @return array
     */
    private function disconnect($socket) {
        $recv_msg = [
            'type' => 'logout',
            'content' => $this->sockets[(int)$socket]['uname']
        ];
        unset($this->sockets[(int)$socket]);
        return $recv_msg;
    }

    /**
     * 用公共握手算法握手
     *
     * @param $socket
     * @param $buffer
     *
     * @return bool
     */
    public function handShake($socket, $buffer) {
        // 获取到客户端的升级密匙
        $line_with_key = substr($buffer, strpos($buffer, 'Sec-WebSocket-Key:') + 18);
        $key = trim(substr($line_with_key, 0, strpos($line_with_key, "\r\n")));

        // 生成升级密匙,并拼接websocket升级头
        $upgrade_key = base64_encode(sha1($key . "258EAFA5-E914-47DA-95CA-C5AB0DC85B11", true));// 升级key的算法
        $upgrade_message = "HTTP/1.1 101 Switching Protocols\r\n";
        $upgrade_message .= "Upgrade: websocket\r\n";
        $upgrade_message .= "Sec-WebSocket-Version: 13\r\n";
        $upgrade_message .= "Connection: Upgrade\r\n";
        $upgrade_message .= "Sec-WebSocket-Accept:" . $upgrade_key . "\r\n\r\n";
		// 向socket里写入升级信息
        socket_write($socket, $upgrade_message, strlen($upgrade_message));
        $this->sockets[(int)$socket]['handshake'] = true;

        // 向客户端发送握手成功消息,以触发客户端发送用户名动作;
        $msg = [
            'type' => 'handshake',
            'content' => 'success',
        ];
        $msg = $this->build(json_encode($msg));
        socket_write($socket, $msg, strlen($msg));
        return true;
    }

    /**
     * 解析数据
     *
     * @param $buffer
     *
     * @return bool|string
     */
    private function parse($buffer) {
        $decoded = '';
        $len = ord($buffer[1]) & 127;
        if ($len === 126) {
            $masks = substr($buffer, 4, 4);
            $data = substr($buffer, 8);
        } else if ($len === 127) {
            $masks = substr($buffer, 10, 4);
            $data = substr($buffer, 14);
        } else {
            $masks = substr($buffer, 2, 4);
            $data = substr($buffer, 6);
        }
        for ($index = 0; $index < strlen($data); $index++) {
            $decoded .= $data[$index] ^ $masks[$index % 4];
        }

        return json_decode($decoded, true);
    }

    /**
     * 将普通信息组装成websocket数据帧
     *
     * @param $msg
     *
     * @return string
     */
    private function build($msg) {
        $frame = [];
		$data = '';
        $frame[0] = '81';
        $len = strlen($msg);
		
        if ($len < 126) {
            $frame[1] = $len < 16 ? '0' . dechex($len) : dechex($len);
        } else if ($len < 65025) {
            $s = dechex($len);
            $frame[1] = '7e' . str_repeat('0', 4 - strlen($s)) . $s;
        } else {
            $s = dechex($len);
            $frame[1] = '7f' . str_repeat('0', 16 - strlen($s)) . $s;
        }

        for ($i = 0; $i < $len; $i++) { $data .= dechex(ord($msg{$i})); }
        $frame[2] = $data;
		//转str
        $data = implode('', $frame);
		//return hex
        return pack("H*", $data);
    }

    /**
     * 拼装信息
     *
     * @param $socket
     * @param $recv_msg
     * @return string
     */
    private function dealMsg($socket, $recv_msg) {
        $msg_type = $recv_msg['type'];
        $msg_content = $recv_msg['content'];
        $response = [];

        switch ($msg_type) {
            case 'login':
                $this->sockets[(int)$socket]['uname'] = $msg_content;
                // 取得最新的名字记录
                $user_list = array_column($this->sockets, 'uname');
                $response['type'] = 'login';
                $response['content'] = $msg_content;
                $response['user_list'] = $user_list;
                break;
            case 'logout':
                $user_list = array_column($this->sockets, 'uname');
                $response['type'] = 'logout';
                $response['content'] = $msg_content;
                $response['user_list'] = $user_list;
                break;
            case 'user':
                $uname = $this->sockets[(int)$socket]['uname'];
                $response['type'] = 'user';
                $response['from'] = $uname;
                $response['content'] = $msg_content;
                break;
        }

        return $this->build(json_encode($response));
    }

    /**
     * 广播消息
     *
     * @param $data
     */
    private function broadcast($data) {
        foreach ($this->sockets as $socket) {
            if ($socket['resource'] == $this->master) { continue; }
            socket_write($socket['resource'], $data, strlen($data));
        }
    }


}

$ws = new WebSocket("127.0.0.1", "8080");