<!--

@ author: 		DYBOY
@ time:   		2018-12-18
@ description:	基于Scocket的端到端在线群聊，不保留任何消息记录。
-->

<!DOCTYPE html>
<html lang="zH">
<head>
	<meta charset="UTF-8">
	<title>AnyChat - Design by DYBOY</title>
	<meta http-equiv="X-UA-Compatible" content="IE=11,IE=10,IE=9,IE=8"> <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=0, minimum-scale=1.0, maximum-scale=1.0">
	<link rel="shortcut icon" href="http://www.dyboy.cn/content/templates/dy_dux/favicon.ico">

	<link rel="stylesheet" type="text/css" href="//apps.bdimg.com/libs/bootstrap/3.3.4/css/bootstrap.min.css">
	<link rel="stylesheet" type="text/css" href="http://cdn.bootcss.com/font-awesome/4.5.0/css/font-awesome.min.css">
	<link rel="stylesheet" type="text/css" href="./style/main.css">
	<script src="http://apps.bdimg.com/libs/jquery/2.1.4/jquery.min.js"></script>
	<script src="http://libs.baidu.com/bootstrap/3.0.3/js/bootstrap.min.js"></script>

</head>
<body>
	
	<!-- 顶部 -->
	<header>
		<div class="logo">
			AnyChat
		</div>
		<div class="zaixian_num">
			当前在线人数：<span id="zaixian_num">0</span> 人	
		</div>
	</header>

	<!-- 聊天窗口 -->
	<div class="chat_main">
		<div class="container-fluid">

			<div class="row chat_title">
				<span class="users">群魔乱舞</span> 聊天室 
			</div>
			
			<div id="user_list" style="display:none;"></div>
			
			<div class="row"  id="chat_detail" style="padding-top: 10px;overflow:auto;overflow-x:hidden;height:410px;">

			</div>


		</div>


		<div class="chat_bottom">
			<input type="chat_text" id="chat_text" name="chat_text" placeholder="请输入内容">
			<button id="btn_send" onclick="send();">发送</button>
		</div>


	</div>
	



	<!-- 页面底部 -->
	<footer>
		AnyChat - 任意聊，任意撩！
		<br/>
		<a style="color:#FFF;text-decoration:none;" href="https://blog.dyboy.cn/" target="_blank"><i class="fa fa-github"></i>开源地址</a>
	</footer>
	
	
	
	
	<script type="text/javascript">
		
		//输入用户名
		var uname = prompt("请输入用户名：","user"+uuid(5,20));
		while(!uname){ uname = prompt("请输入用户名(不能为空)：","user"+uuid(5,20)); }
		$("#chat_text").focus();
		log('正在连接服务器...');	//连接中
		var ws = new WebSocket("ws://192.168.1.211:8080");
		
		//成功连接
		ws.onopen = function(){
			log("成功连接服务器！");
		}

		//收到消息
		ws.onmessage = function(e){
			var msg = JSON.parse(e.data);
			var sender, user_name, name_list, change_type;
			
			switch(msg.type){
				case 'system':
					log(msg.content);
					return;
				case 'user':
					sender = msg.from;
					getMsg(msg.content, sender);
					return;
				case 'handshake':
					var user_info = {'type': 'login', 'content': uname};
					sendMsg(user_info);
					return;
				case 'login':
				case 'logout':
					user_name = msg.content;
					name_list = msg.user_list;
					change_type = msg.type;
					dealUser(user_name, change_type, name_list);
					return;
			}
			
		}
		
		//服务器错误
		ws.onerror = function () {
			log("连接服务器失败，服务器无应答！");
		};
		
		//系统消息log
		function log( text ) {
			$log = $('#chat_detail');  
			$log.append('<h5 class="syslog">'+text+" "+CurentTime()+"</h5>");
			//Autoscroll
			$("#chat_detail").scrollTop($("#chat_detail")[0].scrollHeight);
		}

		//用户格式化发送消息
		function send() {
			var val_text = $("#chat_text").val();
			if(val_text){
				var reg = new RegExp("\r\n", "g");
				content = val_text.replace(reg, "");
				var msg = {'content': content.trim(), 'type': 'user'};
				sendMsg(msg);
				
				// 添加到聊天框
				$("#chat_text").val("");
				$("#chat_detail").append('<div class="chat_box_right"><div class="user_head_right"><img src="http://www.dyboy.cn/admin/views/images/avatar.jpg"></div><div class="user_name_right">'+uname+'</div><div class="chat_content_right">'+val_text+'</div></div><div style="height:8px;"></div>');
					$("#chat_detail").scrollTop($("#chat_detail")[0].scrollHeight);
			}
			else{
				$("#chat_text").focus();
			}
		}
		
		//向服务器发送消息
		function sendMsg(msg) {
			var data = JSON.stringify(msg);
			ws.send(data);
		}
		
		//生成唯一id
		function uuid(len, radix) {
			var chars = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz'.split('');
			var uuid = [], i;
			radix = radix || chars.length;
			if (len) {
				for (i = 0; i < len; i++) uuid[i] = chars[0 | Math.random() * radix];
			} else {
				var r;
				uuid[8] = uuid[13] = uuid[18] = uuid[23] = '-';
				uuid[14] = '4';
				for (i = 0; i < 36; i++) {
					if (!uuid[i]) {
						r = 0 | Math.random() * 16;
						uuid[i] = chars[(i == 19) ? (r & 0x3) | 0x8 : r];
					}
				}
			}
			return uuid.join('');
		}

		//发送消息  回车键
		document.onkeydown = function(e){
			if(e.keyCode == 13){
				send();
			}
		}
		
		//收到消息
		function getMsg(payload,username){
			if(username != uname){
				$("#chat_detail").append('<div class="chat_box_left"><div class="user_head_left"><img src="http://www.dyboy.cn/admin/views/images/avatar.jpg"></div><div class="user_name_left">'+username+'</div><div class="chat_content_left">'+payload+'</div></div>');
				$("#chat_detail").scrollTop($("#chat_detail")[0].scrollHeight);
			}
		}

		//处理用户消息
		function dealUser(user_name, type, name_list) {
			var user_list = document.getElementById("user_list");
			var user_num = document.getElementById("zaixian_num");
			while(user_list.hasChildNodes()) {
				user_list.removeChild(user_list.firstChild);
			}

			for (var index in name_list) {
				var user = document.createElement("p");
				user.innerHTML = name_list[index];
				user_list.appendChild(user);
			}
			user_num.innerHTML = name_list.length;
			user_list.scrollTop = user_list.scrollHeight;

			var change = type == 'login' ? '上线' : '下线';

			var data = '系统消息: ' + user_name + ' 已' + change;
			log(data);
		}
	
		//当前时间
		function CurentTime() { 
			var now = new Date();
			var year = now.getFullYear();       //年
			var month = now.getMonth() + 1;     //月
			var day = now.getDate();            //日
			var hh = now.getHours();            //时
			var mm = now.getMinutes();          //分
			var ss = now.getSeconds();           //秒
			var clock = year + "-";
			if(month < 10)
				clock += "0";
			clock += month + "-";
			if(day < 10)
				clock += "0";
			clock += day + " ";
			if(hh < 10)
				clock += "0";
			clock += hh + ":";
			if (mm < 10) clock += '0'; 
			clock += mm + ":"; 
			if (ss < 10) clock += '0'; 
			clock += ss; 
			return(clock); 
		}

	</script>
	
</body>
</html>