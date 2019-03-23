<?php

//终端shell通信
$STDIN = fopen('php://stdin', 'r');
$STDOUT = fopen('php://stdout', 'w');

function main (){
    //创建一个socket套接流
    $socket = socket_create(AF_INET6,SOCK_STREAM,SOL_TCP);
    /****************设置socket连接选项，这两个步骤你可以省略*************/
    //接收套接流的最大超时时间1秒，后面是微秒单位超时时间，设置为零，表示不管它
    //socket_set_option($socket, SOL_SOCKET, SO_RCVTIMEO, array("sec" => 1, "usec" => 0));
    //发送套接流的最大超时时间为6秒
    //socket_set_option($socket, SOL_SOCKET, SO_SNDTIMEO, array("sec" => 6, "usec" => 0));
    /****************设置socket连接选项，这两个步骤你可以省略*************/

    //连接服务端的套接流，这一步就是使客户端与服务器端的套接流建立联系
    if(socket_connect($socket,'[::1]', 404) == false){
        fwrite($STDOUT, '链接失败:'.socket_strerror(socket_last_error())."\n");
        return 1;
    }else{
        fwrite($STDOUT, '请输入需要发送的数据'."\n");
        for(;;){
            //获取终端用户输入
            $message = fgets($STDIN);
            if(stripos($message, '关闭') !== false) {
                fwrite($STDOUT, '用户关闭通讯'."\n");
                break;
            }

            //向服务端写入字符串信息
            if(socket_write($socket,$message,strlen($message)) == false){
                fwrite($STDOUT, '发送数据失败:'.socket_strerror(socket_last_error())."\n");
                return 2;
            }else{
                fwrite($STDOUT, '发送数据成功:'."\n");
                //读取服务端返回来的套接流信息--每次读取1024字节数据--直到换行回车字符串结束读完
                $callback = '';
                while($callback = socket_read($socket,1024)){
                    $callback .= $callback;
                }
                if($callback === false) {
                    fwrite($STDOUT, '链接中断:'.socket_strerror(socket_last_error())."\n");
                }else{
                    fwrite($STDOUT, '服务端返回数据:'."\n".$callback."\n");
                }
            }
        }
    }
    socket_close($socket);//工作完毕，关闭套接流
    fclose($STDIN);
    fclose($STDOUT);
    return 0;
}


return main();
