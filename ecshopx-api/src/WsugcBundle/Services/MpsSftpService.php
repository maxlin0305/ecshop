<?php

namespace WsugcBundle\Services;


class MpsSftpService{

    public $conn    = NULL;
    // 连接为NUL
    public $ressftp = NULL;

    public $use_pubkey_file=true;
    //sftp resource
    // 初始化，$config是sftp的连接信息
    public function __construct($config) {
        if (!$this->ressftp) {
            //print_r($config);exit;

            $methods = array(
                'hostkey'=>'ssh-rsa,ssh-dss',
                'client_to_server' => array(
                    'crypt' => 'aes256-ctr,aes192-ctr,aes128-ctr,aes256-cbc,aes192-cbc,aes128-cbc,3des-cbc,blowfish-cbc',
                    'comp' => 'none'),
                'server_to_client' => array(
                    'crypt' => 'aes256-ctr,aes192-ctr,aes128-ctr,aes256-cbc,aes192-cbc,aes128-cbc,3des-cbc,blowfish-cbc',
                    'comp' => 'none'));
            ini_set('default_socket_timeout', 60*20); //设置超时时间20分钟
            if($this->use_pubkey_file){
                $this->conn = ssh2_connect($config['host'], $config['port'], $methods);
            }else{
                $this->conn = ssh2_connect($config['host'], $config['port'],$methods);
            }
            //$this->conn = ssh2_connect($config['host'], $config['port']);
            app('log')->debug('feed ftp服务器 请求参数：---->>>>'."\n".json_encode($config,JSON_UNESCAPED_UNICODE));

            if (ssh2_auth_pubkey_file($this->conn, $config['user'], $config['public_key'],$config['private_key'],$config['password'])) {
                $this->ressftp = ssh2_sftp($this->conn);
                //启动引力传动系统
            } else {
                echo("用戶名或密碼錯誤");
                die();
            }
        }
        return $this->ressftp;

    }
    /**
     * 判段远程目录是否存在
     * @param $dir /远程目录
     * @return bool
     */
    public function ssh2_dir_exits($dir) {
        return file_exists("ssh2.sftp://" . intval($this->ressftp) . $dir);
    }

    /**
     * 下载文件
     * @param $remote /远程文件地址
     * @param $local /下载到本地的地址
     * @return bool
     */
    public function downSftp($remote, $local,$file_name,$localDir="") {
        //print 3333;exit;
        //超时 copy("ssh2.sftp://" . intval($this->ressftp) . $remote, $local);
        //print_r($local);exit;
        //获取不到 return  ssh2_scp_recv($this->conn,$remote, $local);
        //32s
        // $content = file_get_contents("ssh2.sftp://" . intval($this->ressftp) .$remote);
        // file_put_contents( $local,$content);

        if (!$fh = fopen("ssh2.sftp://".intval($this->ressftp).$remote, 'r')) {
            die("Failed to open file\n");
        }

        $length=1024*512;//512k
        //file_put_contents( $local,"");
        // while (($content = fgets($fh,$length)) !== false) {
        //     //echo $s;
        //     file_put_contents( $local,$content,FILE_APPEND);

        // }
        $i=0;
        $linesplit=1000;
        $local_base=str_replace('.csv','',$local);
        $file_name_base=str_replace('.csv','',$file_name);
        $fileNames=[];
        $mpsFeedUploadService=new MpsFeedUploadService();
        $header=array_values($mpsFeedUploadService->header);

        while (($content = fgetcsv($fh,$length,';')) !== false) {
            //echo $s;
            
            if($i%$linesplit==0 || $i==0){
                $local=$local_base.'_'.($i/$linesplit).'.csv';
                $fileNames[]=$file_name_base.'_'.($i/$linesplit).'.csv';
                $fp = fopen( $local, 'w+');
            }
            if($i%$linesplit==0 && $i!=0){
                //第二个文件开始要加上头部，头部要加上2022-09-27 09:29:30
                fputcsv( $fp,$header);
            }
            fputcsv( $fp,$content);
          
            $i++;
        }
        fclose($fh);
        unset($this->ressftp);
        app('log')->debug('feed 拆分的 fileNames：---->>>>'."\n".json_encode($fileNames,JSON_UNESCAPED_UNICODE));
        return $fileNames;
    }
    /**
     * 文件上传
     * @param $local /本地文件地址
     * @param $remote /上传后的文件地址
     * @param int $file_mode
     * @return bool
     */
    public function upSftpOld($local, $remote, $file_mode = 0777) {
        return copy($local, "ssh2.sftp://" . intval($this->ressftp) . $remote);
    }

    public function upSftp($local_file, $remote_file)
    {
        $sftp = $this->ressftp;
        $stream = @fopen("ssh2.sftp://".intval($sftp).$remote_file, 'w');
 
        app('log')->debug('上传文件到upSftp：---->>>>'."\r\n local_file:\r\n".$local_file.';remote_file:\r\n'.$remote_file);

        if (! $stream)
            throw new \Exception("Could not open file: $remote_file");
 
        $data_to_send = @file_get_contents($local_file);
        if ($data_to_send === false)
            throw new \Exception("Could not open local file: $local_file.");
 
        if (@fwrite($stream, $data_to_send) === false){
            app('log')->debug('上传文件到upSftp：---->>>>'."\r\n local_file:\r\n".$local_file.';remote_file:\r\n'.$remote_file."error:Could not send data from file");
            throw new \Exception("Could not send data from file: $local_file.");
        }
 
        @fclose($stream);
    }
    /**
     * 删除远程目录中文件
     * @param $file
     * @return bool
    */

    public function deleteSftp($file) {
        return ssh2_sftp_unlink($this->ressftp, $file);
    }

    /**
     * 遍历远程目录
     * @param $remotePath
     * @return array
     */
    public function fileList($remotePath) {
        $fileArr = scandir('ssh2.sftp://' . intval($this->ressftp) . $remotePath);
        foreach ($fileArr as $k => $v) {
            // if ($v == '.' || $v == '..') {
            //     unset($fileArr[$k]);
            // }
        }
        return $fileArr;
    }
    /**
     * 创建远程目录中文件夹
     * @param $fil
     * @return bool
     */
    public function ssh2_sftp_mkdir($dir) {
        return ssh2_sftp_mkdir($this->ressftp, $dir);
    }
    public function deleteDir($path)
    {

        if (is_dir($path)) {

            //扫描一个目录内的所有目录和文件并返回数组
    
            $dirs = scandir($path);
    
            foreach ($dirs as $dir) {
    
                //排除目录中的当前目录(.)和上一级目录(..)
    
                if ($dir != '.' && $dir != '..') {
    
                    //如果是目录则递归子目录，继续操作
    
                    $sonDir = $path.'/'.$dir;
    
                    if (is_dir($sonDir)) {
    
                        //递归删除
    
                        $this->deleteDir($sonDir);
    
                        //目录内的子目录和文件删除后删除空目录
    
                        @rmdir($sonDir);
    
                    } else {
    
                        //如果是文件直接删除
    
                        @unlink($sonDir);
    
                    }
    
                }
    
            }
    
            @rmdir($path);
    
    }
    }
}
