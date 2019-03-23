<?php

//终端shell通信
//STDIN = fopen('php://stdin', 'r');
//STDOUT = fopen('php://stdout', 'w');

function child_return(){
    $return_num = 0;
    $pid = pcntl_wait($return_num);

    fwrite(STDOUT, '子进程:'.(string)$pid.'退出;返回值:'.(string)$return_num."\n");
}

function main (){
    //创建服务端的socket套接流,net协议为IPv4，protocol协议为TCP
    $socket = socket_create(AF_INET6,SOCK_STREAM,SOL_TCP);

    /*绑定接收的套接流主机和端口,与客户端相对应*/
    if(socket_bind($socket,'::', 404) == false){
        fwrite(STDOUT, '绑定地址端口失败:'.socket_strerror(socket_last_error())."\n");
        return 1;
    }
    //监听套接流--等待队列8
    if(socket_listen($socket, 8)==false){
        fwrite(STDOUT, '监听管道失败:'.socket_strerror(socket_last_error())."\n");
        return 2;
    }
    //注册子进程退出信号
    pcntl_signal(SIGCHLD, 'child_return');

    //让服务器无限获取客户端传过来的信息
    do{
        fwrite(STDOUT, '等待客户端连接:'."\n");
        /*接收客户端传过来的信息*/
        $accept_resource = socket_accept($socket);
        /*socket_accept的作用就是接受socket_bind()所绑定的主机发过来的套接流*/

        if($accept_resource !== false){
            $pid = pcntl_fork();
            if($pid == 0) {

                //读取客户端传过来的套接流信息--每次读取1024字节数据--直到换行回车字符串结束读完
                $message = '';
                while($message = socket_read($accept_resource,1024)){
                    fwrite(STDOUT, '数据循环'.$message."\n");
                    $message .= $message;
                }
                if($message === false) {
                    fwrite(STDOUT, '链接中断:'.socket_strerror(socket_last_error())."\n");
                }else{
                    fwrite(STDOUT, '客户端发送数据:'."\n".$message."\n");
                    /*socket_write的作用是向socket_create的套接流写入信息，或者向socket_accept的套接流写入信息*/
                    $return_client = '服务器收到消息为: '.$message."\n";
                    socket_write($accept_resource,$return_client,strlen($return_client));

                    if(stripos($message, '关闭') !== false) {
                        $close_connect = '关闭通信';
                        socket_write($accept_resource,$close_connect,strlen($close_connect));
                        //socket_close的作用是关闭socket_create()或者socket_accept()所建立的套接流
                        socket_close($accept_resource);
                        break;
                    }
                }
            } else {
                //主进程关闭TCP流
                socket_close($accept_resource);
                fwrite(STDOUT, '生成子进程:'.(string)$pid."\n");
            }
        }
    }while(true);

    socket_close($socket);

    return 0;
}



return main();
