<?php
namespace Common\Controller;

class PayConfig{
    private $cfg = array(
        'url'=>'https://pay.swiftpass.cn/pay/gateway',
        'mchId'=>'101580001078',
        'key'=>'f56956de4472c3bc110f495db938205a',
        'version'=>'1.0'
       );
    
    public function PayC($cfgName){
        return $this->cfg[$cfgName];
    }
}
?>