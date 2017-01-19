<?php
/**
 * Magento
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to atendimento@saffira.com.br so we can send you a copy immediately.
 *
 * @category   Saffira / maxiPago
 * @package    MaxiPago_CheckoutApi
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

class MaxiPago_CheckoutApi_Helper_Adress extends Mage_Core_Helper_Data
{
    
	private $completion = array("casa", "ap", "apto", "apart", "frente", "fundos", "sala", "cj");
	private $df         = array("bloco", "setor", "quadra", "lote");
	private $noDf       = array("av", "avenida", "rua", "alameda", "al.", "travessa", "trv", "praça", "praca");
	private $without    = array("sem ", "s.", "s/", "s. ", "s/ ");
	private $number     = array('n.º', 'nº', "numero", "num", "número", "núm", "n");
	
	/**
	 * getAdress
	 * @param string $adress 
	 * @return array
	 */
    public function getAdress($adress) {
        if ($this->isDf($adress)) {
          $number = 's/nº';
          list($street, $completion) = $this->partCompletion($adress);
        } else {
          $splited = preg_split('/[-,]/', $adress);
          if (in_array(sizeof($splited), array(2, 3))) { 
            list($street, $number, $completion) = $splited;
          } else {
            list($street, $number) = $this->reverse($adress);
          }
          $street = $this->cleanNumber($street);
          if (strlen($completion)==0)
            list($numberb,$completion) = $this->partNumber($number);
        }
        return array($this->endtrim($street), $this->endtrim($number), $this->endtrim($completion));
    }
	
	/**
	 * partNumber
	 * @param string $n
	 * @return array
	 */
    public function partNumber($n) {
        $withoutNumber = $this->withoutNumber();
        $n = $this->endtrim($n);
        foreach ($withoutNumber as $sn) {
          if ($n == $sn)return array($n, '');
          if (substr($n, 0, strlen($sn)) == $sn)
            return array(substr($n, 0, strlen($sn)), substr($n, strlen($sn)));
        }
        $q = preg_split('/\D/', $n);
        $pos = strlen($q[0]);
        return array(substr($n, 0, $pos), substr($n,$pos));
    }
    
	/**
	 * partCompletion
	 * @param string $adress   
	 * @return array
	 */
    function partCompletion($adress) {
        foreach ($this->$completion as $c)
          if ($pos = strpos(strtolower($adress), $c))
            return array(substr($adress, 0 ,$pos), substr($adress, $pos));
        return array($adress, '');
    }
    
	/**
	 * @param string
	 * @return string
	 */
    function endtrim($e){
        return preg_replace('/^\W+|\W+$/', '', $e);
    }
	
	/**
	 * cleanNumber
	 * @param string $street   Endereço a ser tratado
	 * @return string
	 */
    function cleanNumber($street) {
        foreach ($this->number as $n)
          foreach (array(" $n"," $n ") as $N)
          if (substr($street, -strlen($N)) == $N)
            return substr($street, 0, -strlen($N));
        return $street;
    }
    
	/**
	 * @param string $adress
	 * @return array
	 */
    function reverse($adress) {
        $find = substr($adress, -10);
        for ($i = 0; $i < 10; $i++) {
        	if (is_numeric(substr($find, $i, 1))) {
				return array( substr($adress, 0, -10+$i), substr($adress, -10+$i) );
			}
        }
    }
    
	/**
	 * @param string $adress 
	 * @return bool
	 */
    function isDf($adress) {
        $df = false;
        foreach ($this->df as $b)
        	if (strpos(strtolower($adress),$b) != false)
            	$df = true;
        if ($df)
			foreach ($this->noDf as $b)
				if (strpos(strtolower($adress),$b) != false)
					$df = false;
        return $df;
    }

	/**
	 * @return array
	 */
    function withoutNumber() {
        foreach ($this->number as $n)
        	foreach ($this->without as $s)
            	$withoutNum[] = "$s$n";
        return $withoutNum;
    }
    
}