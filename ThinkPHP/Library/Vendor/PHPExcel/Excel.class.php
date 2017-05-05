<?php

/**
 * @ Excel扩展类
 * @ Excel 导入/导出
 * @ Author Jamlin@163.com
 * @ Date 2015-6
 */
class Excel
{
    //配置
    static public $config = array(
        'remove' => false,        //是否上传后删除文件
        'filename' => 'filename', //文件名称
        'rootpath' => './Public', //上传主目录
        'savepath' => '/Uploads/Files/Excel/',//上传子目录
        'filetype' => array('xls', 'xlsx'),//限制上传文件类型
        'fields' => array(),//导入/导出文件字段[导入时为数据字段,导出时为字段标题]
        'datefield' => array(),//上传带日期时间格式字段
        'data' => array(), //导出Excel的数组
        'savename' => '',  //导出文件名称
        'title' => '',     //导出文件栏目标题
        'suffix' => 'xlsx',//文件格式
    );

    //初始化
    public function __construct($config = array())
    {

        if (empty($config['fields'])) exit('未设置字段集！');
        //设置配置项
        foreach ($config as $key => $value) {
            if (!empty($value)) {
                static::$config[$key] = $value;
            }
        }
    }

    //上传文件
    public function UploadFile()
    {
        $filename = self::$config['filename'];
        
        if (!empty($_FILES[$filename]['name'])) {
            $file_types = explode(".", $_FILES[$filename]['name']);
            $file_type = $file_types[count($file_types) - 1];
            /*判别是不是.xls文件，判别是不是excel文件*/
            if (!in_array(strtolower($file_type), self::$config['filetype'])) {
                exit('您上传的不是Excel文件，重新上传！');
            }
            /*设置上传路径*/
            //实例化上传类
            $config = array('maxSize' => 3145728,
                'rootPath' => self::$config['rootpath'],
                'savePath' => self::$config['savepath'],
                'saveName' => array('uniqid', time()),
                'exts' => self::$config['filetype'],
                'autoSub' => true,
                'subName' => array('date', 'Ym'),
                'hash' => true,
            );
            $upload = new \Think\Upload($config);
            //开始上传
            $info = $upload->uploadOne($_FILES[$filename]);
            //上传错误时
            if (!$info) exit($upload->getError());
            $rootpath = ltrim($config['rootPath'], '.');
            $full_path = $rootpath . $info['savepath'] . $info['savename'];
            return array('filepath' => $full_path, 'filename' => $info['savename']);
        }
    }




    //导入excel内容转换成数组
    public function import()
    {
        
        //上传文件
        $file = $this->UploadFile();
        $filePath = ltrim($file['filepath'], '/');
        //解析Excel
        /*导入phpExcel核心类 */
        Vendor("PHPExcel.PHPExcel");
        Vendor("PHPExcel.PHPExcel.IOFactory");
        $PHPExcel = new \PHPExcel();
        // PHPExcel_Settings::setCacheStorageMethod();
        // PHPExcel_CachedObjectStorageFactory::cache_in_memory_serialized;
        set_time_limit(0);//设置程序执行时间
        /**默认用excel2007读取excel，若格式不对，则用之前的版本进行读取*/
        $PHPReader = \PHPExcel_IOFactory::createReader('Excel2007');
        if (!$PHPReader->canRead($filePath)) {
            $PHPReader = \PHPExcel_IOFactory::createReader('Excel5');
            if (!$PHPReader->canRead($filePath)) {
                exit('no Excel');
            }
        }

        $PHPExcel = $PHPReader->load($filePath, 'gb2312');
        $currentSheet = $PHPExcel->getSheet(0);  //读取excel文件中的第一个工作表
        $allColumn = $currentSheet->getHighestColumn(); //取得最大的列号
        $allRow = $currentSheet->getHighestRow(); //取得一共有多少行
        //声明数组
        $data = array();
        //列标识数组
        $letters_arr = array('A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P', 'Q', 'R', 'S', 'T', 'U', 'V', 'W', 'X', 'Y', 'Z', 'AA', 'AB', 'AC', 'AD', 'AE', 'AF', 'AG', 'AH', 'AI', 'AJ', 'AK', 'AL', 'AM', 'AN', 'AO', 'AP', 'AQ', 'AR', 'AS', 'AT', 'AU', 'AV', 'AW', 'AX', 'AY', 'AZ');
        $fields = self::$config['fields'];
        $letters_arr = array_slice($letters_arr, 0, count($fields));
        /**从第二行开始输出，因为excel表中第一行为列名*/
        $i = 0;
        for ($currentRow = 2; $currentRow <= $allRow; $currentRow++) {
            /**从第A列开始输出*/
            for ($currentColumn = 0; $currentColumn <= array_search($allColumn, $letters_arr); $currentColumn++) {
                $val = $PHPExcel->getActiveSheet()->getCell("{$letters_arr[$currentColumn]}{$currentRow}")->getValue();
                if (!empty($val)) {//富文本转换字符串
                    if ($val instanceof PHPExcel_RichText) {
                        $val = $val->__toString();
                    }
                    //转换日期格式
                    if (!empty(self::$config['datefield']) && in_array($fields[$currentColumn], self::$config['datefield']))
                        $val = $this->excelTime($val);
                    $data[$i][$fields[$currentColumn]] = $val;
                    /**如果输出汉字有乱码，则需将输出内容用iconv函数进行编码转换，如下将gb2312编码转为utf-8编码输出*/
                    //echo iconv('utf-8','gb2312', $val)."\t";
                }
            }
            $i++;
        }
        //删除文件
        if (self::$config['remove']) {
            $dfile = '.' . $file['filepath'];
            if (file_exists($dfile)) {
                unlink($dfile);
            }
        }
        return $data;
    }


    //导入excel内容转换成数组
    public function importPrivata()
    {
        
        //上传文件
        $file = $this->UploadFile();
        $filePath = ltrim($file['filepath'], '/');
        //解析Excel
        /*导入phpExcel核心类 */
        Vendor("PHPExcel.PHPExcel");
        Vendor("PHPExcel.PHPExcel.IOFactory");
        $PHPExcel = new \PHPExcel();
        // PHPExcel_Settings::setCacheStorageMethod();
        // PHPExcel_CachedObjectStorageFactory::cache_in_memory_serialized;
        set_time_limit(0);//设置程序执行时间
        /**默认用excel2007读取excel，若格式不对，则用之前的版本进行读取*/
        $PHPReader = \PHPExcel_IOFactory::createReader('Excel2007');
        if (!$PHPReader->canRead($filePath)) {
            $PHPReader = \PHPExcel_IOFactory::createReader('Excel5');
            if (!$PHPReader->canRead($filePath)) {
                exit('no Excel');
            }
        }

        $PHPExcel = $PHPReader->load($filePath, 'gb2312');
        $currentSheet = $PHPExcel->getSheet(0);  //读取excel文件中的第一个工作表
        $allColumn = $currentSheet->getHighestColumn(); //取得最大的列号
        $allRow = $currentSheet->getHighestRow(); //取得一共有多少行
        //声明数组
        $data = array();
        //列标识数组
        $letters_arr = array('A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P', 'Q', 'R', 'S', 'T', 'U', 'V', 'W', 'X', 'Y', 'Z', 'AA', 'AB', 'AC', 'AD', 'AE', 'AF', 'AG', 'AH', 'AI', 'AJ', 'AK', 'AL', 'AM', 'AN', 'AO', 'AP', 'AQ', 'AR', 'AS', 'AT', 'AU', 'AV', 'AW', 'AX', 'AY', 'AZ');
        $fields = self::$config['fields'];
        $letters_arr = array_slice($letters_arr, 0, count($fields));
        /**从第二行开始输出，因为excel表中第一行为列名*/
        $i = 0;
        for ($currentRow = 2; $currentRow <= $allRow; $currentRow++) {
            /**从第A列开始输出*/
            for ($currentColumn = 0; $currentColumn <= array_search($allColumn, $letters_arr); $currentColumn++) {
                if($currentColumn==2)
                {
                    continue;
                }
                $val = $PHPExcel->getActiveSheet()->getCell("{$letters_arr[$currentColumn]}{$currentRow}")->getValue();
                if (!empty($val)) {//富文本转换字符串
                    if ($val instanceof PHPExcel_RichText) {
                        $val = $val->__toString();
                    }
                    //转换日期格式
                    if (!empty(self::$config['datefield']) && in_array($fields[$currentColumn], self::$config['datefield']))
                        $val = $this->excelTime($val);
                    $data[$i][$fields[$currentColumn]] = $val;
                    /**如果输出汉字有乱码，则需将输出内容用iconv函数进行编码转换，如下将gb2312编码转为utf-8编码输出*/
                    //echo iconv('utf-8','gb2312', $val)."\t";
                }
            }         
           
           
            $datanew = array();
            $datanew['shop_id']=$data[$i]['shop_id'];
            $datanew['menu_id']=$data[$i]['menu_id'];
            $datanew['order_date']=$data[$i]['order_date'];
            $count = D("Product")->where($datanew)->count();
            if($count<=0)
            {
                if(empty($data[$i]['name']))
                {
                    $datanew['name']='';
                }else
                {
                    $datanew['name']=$data[$i]['name'];
                }
                
                $datanew['price']=$data[$i]['price'];              

                $t = D("Product")->add($datanew);   //向产品表中插入数据

            }           
           
            $i++;
        }
        //删除文件
        if (self::$config['remove']) {
            $dfile = '.' . $file['filepath'];
            if (file_exists($dfile)) {
                unlink($dfile);
            }
        }
        return $data;
    }

    //生成uid 方法
    public function guid(){
        
        mt_srand((double)microtime()*10000);//optional for php 4.2.0 and up.
        $charid = strtoupper(md5(uniqid(rand(), true)));
        $hyphen = chr(45);// "-"
        $uuid = chr(123)// "{"
        .substr($charid, 0, 8).$hyphen
        .substr($charid, 8, 4).$hyphen
        .substr($charid,12, 4).$hyphen
        .substr($charid,16, 4).$hyphen
        .substr($charid,20,12).chr(125);// "}"
        
        return $uuid;
            
    }

    //导入excel内容转换成数组
    public function importOrder()
    {   
        cookie("prevUrl", "Admin/Order/order/page/1");
   
        //上传文件
        $file = $this->UploadFile();
        $filePath = ltrim($file['filepath'], '/');
        //解析Excel
        /*导入phpExcel核心类 */
        Vendor("PHPExcel.PHPExcel");
        Vendor("PHPExcel.PHPExcel.IOFactory");
        $PHPExcel = new \PHPExcel();
        // PHPExcel_Settings::setCacheStorageMethod();
        // PHPExcel_CachedObjectStorageFactory::cache_in_memory_serialized;
        set_time_limit(0);//设置程序执行时间
        /**默认用excel2007读取excel，若格式不对，则用之前的版本进行读取*/
        $PHPReader = \PHPExcel_IOFactory::createReader('Excel2007');
        if (!$PHPReader->canRead($filePath)) {
            $PHPReader = \PHPExcel_IOFactory::createReader('Excel5');
            if (!$PHPReader->canRead($filePath)) {
                exit('no Excel');
            }
        }

        $PHPExcel = $PHPReader->load($filePath, 'gb2312');
        $currentSheet = $PHPExcel->getSheet(0);  //读取excel文件中的第一个工作表
        $allColumn = $currentSheet->getHighestColumn(); //取得最大的列号
        $allRow = $currentSheet->getHighestRow(); //取得一共有多少行
        //声明数组
        $data = array();
        //列标识数组
        $letters_arr = array('A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P', 'Q', 'R', 'S', 'T', 'U', 'V', 'W', 'X', 'Y', 'Z', 'AA', 'AB', 'AC', 'AD', 'AE', 'AF', 'AG', 'AH', 'AI', 'AJ', 'AK', 'AL', 'AM', 'AN', 'AO', 'AP', 'AQ', 'AR', 'AS', 'AT', 'AU', 'AV', 'AW', 'AX', 'AY', 'AZ');
        $fields = self::$config['fields'];
        $letters_arr = array_slice($letters_arr, 0, count($fields));
        /**从第二行开始输出，因为excel表中第一行为列名*/
        $i = 0;
        for ($currentRow = 2; $currentRow <= $allRow; $currentRow++) {
            /**从第A列开始输出*/
            for ($currentColumn = 0; $currentColumn <= array_search($allColumn, $letters_arr); $currentColumn++) {
                
                $val = $PHPExcel->getActiveSheet()->getCell("{$letters_arr[$currentColumn]}{$currentRow}")->getValue();
                if (!empty($val)) {//富文本转换字符串
                    if ($val instanceof PHPExcel_RichText) {
                        $val = $val->__toString();
                    }
                    //转换日期格式
                    if (!empty(self::$config['datefield']) && in_array($fields[$currentColumn], self::$config['datefield']))
                        $val = $this->excelTime($val);
                    $data[$i][$fields[$currentColumn]] = $val;
                    /**如果输出汉字有乱码，则需将输出内容用iconv函数进行编码转换，如下将gb2312编码转为utf-8编码输出*/
                    //echo iconv('utf-8','gb2312', $val)."\t";
                }
            }
                   
            $datanew = array();
            $datanew['school_name']=$data[$i]['学校名'];
            $datanew['class_name']=$data[$i]['班级名'];
            $datanew['student_name']=   $data[$i]['学生名'];
            $datanew['shop_name']=$data[$i]['商家名'];
            $datanew['menu_name']=$data[$i]['套餐名'];
            $money = $data[$i]['金额'];

            $datao['totalprice']=$money;
            $school = D('XjSchool')->where(array('xxmc'=>$data[$i]['学校名']))->field('id')->find();   //根据学校查询
            if(!empty($school))
            {

                $conClass=array('bjmc'=>$data[$i]['班级名']);
                array_push($conClass, array("school_id" =>  $school['id']));
                $class = D('XjClass')->where($conClass)->field('id')->find();  //查询班级
                if(!empty($class))
                {
                    $conditionStu = array('xsxm'=>$data[$i]['学生名']);
                    array_push($conditionStu, array("bj" =>  $class['id']));
                    $students = D('XjStudent')->where($conditionStu)->select();   //查询学生信息
                    if(!empty($students)&&!empty($students[0]['dyzh'])) 
                    {
                        //var_dump(D('XjStudent')->getLastSql());
                    /*var_dump($students[0]['id']);*/
                    $custId=$students[0]['dyzh'];           
                    $datao['user_id']= $custId;
                    $stuSize = count($students);   //学生数目

                    //如果同一个班级学生有重名现象则存在这个表中
                    if($stuSize>1)
                    {

                        for($h=0;$h<$stuSize;$h++)
                        {

                            $dat['column_id'] = $students[$h]['id'];
                            $dat['column_name'] =$students[$h]['xsxm'] ;
                            $ppp = D('RepeatedItems')->add($dat);  
                            $tp = D('RepeatedItems')->getLastSql();                    
                            
                        }
                    }

                   // var_dump(D('XjStudent')->getLastSql());
                    $shop = D('Shop')->where(array('name'=>$data[$i]['商家名']))->field('id')->find();  //店铺信息
                    if(!empty($shop)&&!empty($shop['id']))
                    {
                        $merchantId = $shop['id'];
                        $datao['shop_id']=$merchantId;

                        $conditionMenu = array('name'=>$data[$i]['套餐名']);
                        array_push($conditionMenu, array('shop_id'=>$merchantId));
                        $menu = D('Menu')->where($conditionMenu)->order('time desc')->limit(1)->find();    //查询菜单信息

                        if(!empty($menu)&&!empty($menu['id']))
                        {
                            //var_dump($menu);
                            $datao['menu_id']=$menu['id'];
                            $conProduct = array('menu_id'=>$menu['id']);
                            array_push($conProduct, array('shop_id'=>$merchantId));  

                            $product = D('Product')->where($conProduct)->order('order_date asc')->select();
                            if(!empty($product))
                            {
                                /*var_dump($product);            
                                var_dump($product[0]);*/
                                $out_trade_no = date('Ymd').time(); 
                                $datao['orderid']= $out_trade_no;  //创建订单id自定义
                                $datao['pay_status']=1;
                                $datao['class_id']=$class['id'];
                                $datao['time']=date('Y-m-d H:i:s');

                                $id = D('Order')->add($datao);        //插入订单信息
                                
                                $dateDetail['order_id'] = $id;
                                $dateDetail['user_id'] = $custId;
                                $dateDetail['num'] = 1;
                                $dateDetail['class_id']=$class['id'];
                                $dateDetail['leave_status']=0;
                                $dateDetail['retreat_status']=0;

                                $productSize = count($product);    //产品总条数
                                //var_dump($productSize);


                                for($o=0;$o<$productSize;$o++)
                                {
                                     $dateDetail['product_id'] = $product[$o]['id'];
                                     $dateDetail['file_id'] = $product[$o]['file_id'];
                                     $dateDetail['price'] = $product[$o]['price'];
                                     $dateDetail['name'] = $product[$o]['name'];
                                     $dateDetail['price'] = $product[$o]['price'];
                                     $dateDetail['time']=date('Y-m-d H:i:s');
                                     $did = D('OrderDetail')->add($dateDetail);    //保存详情订单表                

                                 }

                                 $beginDate = $product[0]['order_date'];
                                 $endDate = $product[$productSize-1]['order_date'];

                                ///////调用充值接口
                                mt_srand((double)microtime()*10000);//optional for php 4.2.0 and up.
                                $charid = strtoupper(md5(uniqid(rand(), true)));
                                $hyphen = chr(45);// "-"
                                $uuid = //chr(123).
                                substr($charid, 0, 8)//.$hyphen
                                .substr($charid, 8, 4)//.$hyphen
                                .substr($charid,12, 4)//.$hyphen
                                .substr($charid,16, 4)//.$hyphen
                                .substr($charid,20,16);         //.chr(125)
                                $openid = $uuid; //openid
                                $orderType = 1;
                                //客户充值的方法调用
                                
                                $urlt = CONNECT."recharge/".$custId."/".$merchantId."/".$openid."/".$out_trade_no."/".$orderType."/".$money."/".$beginDate."/".$endDate;

                                $dataee = array(
                                    "orderid" => $out_trade_no,
                                    "custid" => $custId,
                                    "merchantid" => $merchantId,
                                    "openid" => $openid,
                                    "money" => $money,
                                    "beginDate" => $beginDate,
                                    "endDate" => $endDate,
                                    "url" => $urlt

                                    );
                                $t = D("Callback")->add($dataee);
                                $result = $this->_request($urlt,true,'POST');
                                //echo "result---------------------------------";
                                //var_dump($result);
                                if(empty($result))
                                {
                                    $res = $result.'---返回结果为空';
                                }else
                                {
                                    $res = $result;
                                }                           

                                $result = json_decode($result);                  
                                

                                $dataend = array(
                                 "result" =>$res                  
                                 );
                                D('Callback')->where('id='.$t)->save($dataend);

                            }                           
                            

                        }
                        
                    }
                    
                    }         
                    
                }
                // var_dump($class);   var_dump($class['id']);           
                     

           }
           

            $i++;
            // echo " ---".$i.'------------';
            
        }
        //删除文件
        if (self::$config['remove']) {
            $dfile = '.' . $file['filepath'];
            if (file_exists($dfile)) {
                unlink($dfile);
            }
        }
        return $data;
    }


    //转换时间格式为标准格式
    protected function excelTime($date, $time = false)
    {
        if (function_exists('GregorianToJD')) {
            if (is_numeric($date)) {
                $jd = GregorianToJD(1, 1, 1970);
                $gregorian = JDToGregorian($jd + intval($date) - 25569);
                $date = explode('/', $gregorian);
                $date_str = str_pad($date [2], 4, '0', STR_PAD_LEFT)
                    . "-" . str_pad($date [0], 2, '0', STR_PAD_LEFT)
                    . "-" . str_pad($date [1], 2, '0', STR_PAD_LEFT)
                    . ($time ? " 00:00:00" : '');
                return $date_str;
            }
        } else {
            $date = $date > 25568 ? $date + 1 : 25569;
            /*There was a bug if Converting date before 1-1-1970 (tstamp 0)*/
            $ofs = (70 * 365 + 17 + 2) * 86400;
            $date = date("Y-m-d", ($date * 86400) - $ofs) . ($time ? " 00:00:00" : '');
        }
        return $date;
    }


// @ 导出Excel表格
// @ Params data[*导出二维数组]
// @ Params fields[定义第一行标题,默认为数组字段]
// @ params filename[导出的文件名称，默认为日期名称]
// @ Params title[第一张表标题]
// @ Params suffix[文件格式，默认为xlsx] 
    static public function export($data, $fields = null, $savename = null, $title = 'Sheet1', $suffix = 'xlsx')
    {
        //导出数据
        $data = !empty(self::$config['data']) ? self::$config['data']
            : (!empty($data) ? $data : exit('导出数据为空！'));
        //第一列字段标题
        $fields = !empty(self::$config['fields']) ? self::$config['fields']
            : (!empty($fields) ? $fields : null);
        //文件名称
        $savename = !empty(self::$config['savename']) ? self::$config['savename']
            : (!empty($savename) ? $savename : date('Y-m-d_H_I_s'));
        //表名称
        $title = !empty(self::$config['title']) ? self::$config['title'] : $title;
        //保存文件格式
        $suffix = !empty(self::$config['suffix']) ? self::$config['suffix'] : $suffix;
        //导出的文件全称
        $savename = "{$savename}.{$suffix}";
        /* 实例化类 */
        /*导入phpExcel核心类 */
        Vendor("PHPExcel.PHPExcel");
        $suffix = 'xlsx' ?
            Vendor("PHPExcel.PHPExcel.Writer.Excel2007")
            : Vendor("PHPExcel.PHPExcel.Writer.Excel5");
        $objPHPExcel = new \PHPExcel();

        /* 设置输出的excel文件为2007兼容格式或2003格式 */
        if ($suffix = 'xlsx') {
            $objWriter = new \PHPExcel_Writer_Excel2007($objPHPExcel);//2007格式
        } else {
            $objWriter = new \PHPExcel_Writer_Excel5($objPHPExcel);//非2007格式
        }
        /* 设置当前的sheet */
        $objPHPExcel->setActiveSheetIndex(0);
        $objActSheet = $objPHPExcel->getActiveSheet();
        /*设置宽度*/
        //$objPHPExcel->getActiveSheet()->getColumnDimension('A')->setWidth(10);
        //$objPHPExcel->getActiveSheet()->getColumnDimension('B')->setWidth(60);
        /* sheet标题 */
        $objActSheet->setTitle($title);
        //列标识数组
        $letters_arr = array('A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P', 'Q', 'R', 'S', 'T', 'U', 'V', 'W', 'X', 'Y', 'Z', 'AA', 'AB', 'AC', 'AD', 'AE', 'AF', 'AG', 'AH', 'AI', 'AJ', 'AK', 'AL', 'AM', 'AN', 'AO', 'AP', 'AQ', 'AR', 'AS', 'AT', 'AU', 'AV', 'AW', 'AX', 'AY', 'AZ');
        $i = 2;
        foreach ($data as $value) {
            /* excel文件内容 */
            $j = 0;
            $index = 0;
            foreach ($value as $key => $value2) {
                if ($i == 2) {//设置第一行标题
                    $objActSheet->setCellValue("{$letters_arr[$j]}1", !empty($fields[$index]) ? $fields[$index] : $key);
                    $index++;
                }
                //$value2 = iconv("gb2312","utf-8",$value2);
                $objActSheet->setCellValue($letters_arr[$j] . $i, $value2);
                $j++;
            }
            $i++;
        }


        /* 生成到浏览器，提供下载 */
        ob_end_clean();  //清空缓存
        header("Pragma: public");
        header("Expires: 0");
        header("Cache-Control:must-revalidate,post-check=0,pre-check=0");
        header("Content-Type:application/force-download");
        header("Content-Type:application/vnd.ms-execl");
        header("Content-Type:application/octet-stream");
        header("Content-Type:application/download");
        header("Content-Disposition:attachment;filename={$savename}");
        header("Content-Transfer-Encoding:binary");
        $objWriter->save('php://output');
    }

     /**
     * 实现curl的post和get访问方式
     * @param string $url
     * @param booleam $https
     * @param string $method
     * @param array $data
     */
    public function _request($curl,$https = true,$method='GET',$data = null){
               
        $ch = curl_init();  //初始化
        $this_header = array(
                    "content-type: application/x-www-form-urlencoded; 
                    charset=UTF-8"
                    );
        curl_setopt($ch,CURLOPT_URL,$curl);
        curl_setopt($ch,CURLOPT_HTTPHEADER,$this_header);
        curl_setopt($ch,CURLOPT_RETURNTRANSFER,true); //返回字符串，不直接输出
        //判断是否使用https协议
        if($https){
            curl_setopt($ch,CURLOPT_SSL_VERIFYPEER,false); //不做服务器的验证
            curl_setopt($ch,CURLOPT_SSL_VERIFYHOST,2); //服务器证书验证
        }
        //是否是POST请求
        if($method == 'POST'){
            curl_setopt($ch,CURLOPT_POST,true); //设置为POST请求
            curl_setopt($ch,CURLOPT_POSTFIELDS,$data); //设置POST的请求数据
        }
        $content = curl_exec($ch); //访问指定URL
        curl_close($ch); //关闭cURL释放资源
        return $content;
    } 
}



?>