<?php


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

    //让服务器无限获取客户端传过来的信息
    do{
        fwrite(STDOUT, '等待客户端连接;'."\n");
        /*接收客户端传过来的信息*/
        $accept_resource = socket_accept($socket);
        /*socket_accept的作用就是接受socket_bind()所绑定的主机发过来的套接流*/

        if($accept_resource !== false){
            $pid = pcntl_fork();
            if($pid == 0) {
                //子进程处理管道流
                fwrite(STDOUT, '客户端通讯建立完成;'."\n");
                for(;;) {
                    $message = '';
                    //读取客户端传过来的套接流信息--4k或\n或\r或\0返回数据
                    for(;;) {
                        fwrite(STDOUT, '读取客户端数据;'."\n");
                        $one_com = socket_read($accept_resource,4096);

                        if($one_com === false) {
                            fwrite(STDOUT, '链接中断:'.socket_strerror(socket_last_error())."\n");
                            break;
                        }else if(stripos($one_com, '通讯完毕') !== false) {
                            //跳出读状态
                            break;
                        }else if($one_com === '') {
                            fwrite(STDOUT, '客户端关闭通讯;'."\n");
                            break;
                        }else{
                            fwrite(STDOUT, var_export($one_com, true).';'.strlen($one_com)."\n");
                            $message .= $one_com;
                        }
                    }
                    if(empty($message)) {
                        break;
                    } else {
                        fwrite(STDOUT, '接收到的客户端数据:'."\n".$message."\n");
                        //服务端自定义信息---http服务不需要自定义
                        //获取终端用户输入--存在\n或\r或\0返回数据
                        /*fwrite(STDOUT, '请输入需要发送的数据'."\n");
                        $message = fgets(STDIN);
                        if(stripos($message, '关闭通信') !== false) {
                            $close_connect = '关闭通信';
                            socket_write($accept_resource,$close_connect,strlen($close_connect));
                            //socket_close的作用是关闭socket_create()或者socket_accept()所建立的套接流
                            socket_close($accept_resource);
                            break;
                        }*/
                        /*socket_write的作用是向socket_create的套接流写入信息，或者向socket_accept的套接流写入信息*/
                        $return_client = '服务器收到消息为: '.$message."\n";
                        socket_write($accept_resource,$return_client,strlen($return_client));
                    }
                }
                //子进程关闭TCP流
                socket_close($accept_resource);
                //退出通讯
                break;
            } else {
                //主进程关闭TCP流
                socket_close($accept_resource);
                fwrite(STDOUT, '生成子进程:'.(string)$pid."\n");
            }
        }
    }while(true);
    //关闭端口监听
    socket_close($socket);

    return 0;
}

//https://blog.csdn.net/zhang197093/article/details/77527035
//注册子进程退出信号--垃圾php脚本信号机制不完整
declare(ticks = 1);
pcntl_signal(SIGCHLD, function($signo) {
    $return_num = 0;
    $pid = pcntl_wait($return_num);

    fwrite(STDOUT, '收到信号:'.(string)$signo.'子进程:'.(string)$pid.'退出;返回值:'.(string)$return_num."\n");
});



return main();

