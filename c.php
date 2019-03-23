<?php

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
    if(socket_connect($socket,'::1', 404) == false){
        fwrite(STDOUT, '链接失败:'.socket_strerror(socket_last_error())."\n");
        return 1;
    }else{
        fwrite(STDOUT, '请输入需要发送的数据,结束请输入:关闭通讯;数据输入完毕请输入:通讯完毕'."\n");
        //开始通讯
        for(;;){
            //获取终端用户输入--存在\n或\r或\0返回数据
            $message = fgets(STDIN);
            //清除结尾换行符
            //$message = rtrim($message);
            if(stripos($message, '关闭通讯') !== false) {
                fwrite(STDOUT, '用户关闭通讯'."\n");
                break;
            }

            //向服务端写入字符串信息
            $com_ret = socket_write($socket,$message,strlen($message))
            if($com_ret == false){
                fwrite(STDOUT, '发送数据失败:'.socket_strerror(socket_last_error())."\n");
                return 2;
            }else{
                //客户端数据尚未发送完毕
                if(stripos($message, '通讯完毕') === false) {
                    //跳出读状态
                    continue;
                }

                fwrite(STDOUT, '发送数据成功:'."\n");
                fwrite(STDOUT, '等待服务器返回数据:'."\n");

                for(;;) {
                    //读取服务端返回来的套接流信息--每次读取1024字节数据--直到换行回车字符串结束读完
                    $callback = socket_read($socket, 4096);

                    if($callback === false) {
                        fwrite(STDOUT, '链接中断:'.socket_strerror(socket_last_error())."\n");
                        break;
                    }else if(strlen($callback) < 4096) {
                        //$callback == '通讯完毕' || 服务端固定返回，不需约定结束符
                        //约定切换发送方
                        break;
                    }else{
                        fwrite(STDOUT, '数据循环'.$callback.';数据长度:'.strlen($callback)."\n");
                        $callback .= $callback;
                    }
                }
                if($callback === false) {
                    break;
                } else {
                    fwrite(STDOUT, '服务端返回数据:'."\n".$callback."\n");
                    //客户端主动关闭连接
                    /*if(stripos($callback, '关闭') !== false) {
                        $close_connect = '关闭通信';
                        socket_write($socket,$close_connect,strlen($close_connect));
                        //socket_close的作用是关闭socket_create()或者socket_accept()所建立的套接流
                        socket_close($socket);
                        break;
                    }*/
                }
            }
        }
        socket_close($socket);//工作完毕，关闭管道流
    }
    return 0;
}


return main();
