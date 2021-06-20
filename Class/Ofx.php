<?php

/**
 * Descrição: Classe construtura da estrutura que irá expotar o arquivo OFX em dados editáveis
 * Autor: Vitor Hugo Nunes Marini
 * Data: 19/06/2021
 */

class Ofx {
    
    private $ofxFile;
    
    /**
     * @param type $ofxFile
     * Fução construtora da Class
     */
    public function __construct($ofxFile) {
        $this->ofxFile = $ofxFile;
    } 

    /**
     * @return type XML
     * Função que converte o arquivo OFX em XML Simples -- 
     */
    public function getOfxAsXML() { 
        
        $content        = file_get_contents($this->ofxFile);
        $line           = strpos($content, "<OFX>");
        $ofx            = substr($content, $line - 1);
        $buffer         = $ofx;
        $count          = 0;
        
        #Verifica a posição do buffer e modifica o array
        while ($pos = strpos($buffer, '<')) { 
            
            $count++;
            $pos2    = strpos($buffer, '>');
            $element = substr($buffer, $pos + 1, $pos2 - $pos - 1); 
            
            if (substr($element, 0, 1) == '/') {
                $sla[] = substr($element, 1);
            } else {
                $als[] = $element;
            }
            
            $buffer  = substr($buffer, $pos2 + 1);
        } 
        
        $adif = array_diff($als, $sla);
        $adif = array_unique($adif);
        $ofxy = $ofx;
        
        
        #Faz a "nova" leitura com as "novas" posições.
        foreach ($adif as $dif) { 

            $dpos = 0;

            while ($dpos = strpos($ofxy, $dif, $dpos + 1)) {

                $npos = strpos($ofxy, '<', $dpos + 1);
                $ofxy = substr_replace($ofxy, "</$dif>\n<", $npos, 1);
                $dpos = $npos + strlen($element) + 3;

            } 
        }

        #Limpa o arquivo
        $ofxy = str_replace('&', '&', $ofxy);

        return $ofxy;       
    }
    
    /**
     * @param type $ofx
     * @return type Array
     * Função que traduz as tags do arquivo para facilitar a exportação dos dados, de acordo com as posições.
     */
    public function closeTags($ofx=null) {
        
        $buffer = '';
        $source = fopen($ofx, 'r') or die("Unable to open file!");
        
        while(!feof($source)) {
            
            $line = trim(fgets($source));
            
            if ($line === '') continue;

            if (substr($line, -1, 1) !== '>') {
                list($tag) = explode('>', $line, 2);
                $line .= '</' . substr($tag, 1) . '>';
            }
            
            $buffer .= $line ."\n";
        }

        #Cria o Array através da Tag <OFX>
        $xmlOut =   explode("<OFX>", $buffer);

        #Retorna o Array
        return isset($xmlOut[1])?"<OFX>".$xmlOut[1]:$buffer;
    }
    
    /**
     * 
     * @return $this array()
     * Função que traduz as tags e exporta alguns valores básicos para a leitura do arquivo
     */
    public function returnValues(){
        
        #Alimentamos a variável $ret com os valores exportados das tags
        $ret            =   new SimpleXMLElement(utf8_encode($this->closeTags($this->ofxFile)));
        
        $this->bankTranList =   $ret->BANKMSGSRSV1->STMTTRNRS->STMTRS->BANKTRANLIST->STMTTRN;
        $this->dtStar       =   $ret->BANKMSGSRSV1->STMTTRNRS->STMTRS->BANKTRANLIST->DTSTART;
        $this->dtEnd        =   $ret->BANKMSGSRSV1->STMTTRNRS->STMTRS->BANKTRANLIST->DTEND;
        $this->org          =   $ret->SIGNONMSGSRSV1->SONRS->FI->ORG;
        $this->acctId       =   $ret->BANKMSGSRSV1->STMTTRNRS->STMTRS->BANKACCTFROM->ACCTID;
        $this->bankId       =   $ret->BANKMSGSRSV1->STMTTRNRS->STMTRS->BANKACCTFROM->BANKID;

        #Retorna o array $this;
        return $this;
    }
    

    /**
     * 
     * @return type Array()
     * Função que traduz sem a função closeTags e sim de forma Literal
     */
    public function getBalance() {
        
        #Alimenta a variável $xml convertento os valores brutos
        $xml            = new SimpleXMLElement($this->getOfxAsXML());
        
        $balance        = $xml->BANKMSGSRSV1->STMTTRNRS->STMTRS->LEDGERBAL->BALAMT;
        $dateOfBalance  = $xml->BANKMSGSRSV1->STMTTRNRS->STMTRS->LEDGERBAL->DTASOF;
        $date           = strtotime(substr($dateOfBalance, 0, 8));
        $dateToReturn = date('Y-m-d', $date);
        
        #Retorna o array
        return Array('date' => $dateToReturn, 'balance' => $balance);
    
    } 
    
    /**
     * Retora um array de objetos com as transações    
     * 
     * 
     * #Tradução dos campos básicos retornados para as transações.
     *  DTPOSTED => Data da Transação     
     *  TRNAMT   => Valor da Transação     
     *  TRNTYPE  => Tipo da Transação (Débito ou Crédito)     
     *  MEMO     => Descrição da transação     
     */ 
    public function getTransactions() { 
        
        #Alimentando a variáve $xml com o Array que vem da tag STMTTRN
        $xml            = new SimpleXMLElement($this->getOfxAsXML());
        $transactions = $xml->BANKMSGSRSV1->STMTTRNRS->STMTRS->BANKTRANLIST->STMTTRN;
        
        #Retornando o array com os valores
        return $transactions;
    }
}
