<?php
/**
 * Descrição: Leitor básico para exportação dos valores do documento OFX. Tais valores devem ser manipulados, verificados e conferidos
 *   além de necessária conferência dos dados cadastrais e usuais.
 * Autor: Vitor Hugo Nunes Marini
 * Data: 19/06/2021
 */

error_reporting( E_ERROR ); 

#Importando a Classe
require_once 'Ofx.php';

#Construíndo a função através do arquivo
$ofx = new Ofx('./assets/documento.ofx');

#Extraíndo o valor do saldo.
$saldo      = $ofx->getBalance();
$dataSaldo  = date("d/m/Y", strtotime($saldo['date']));
$valorSaldo = $saldo['balance'];

$html .= '   
<h1>
    Seu saldo em 
    '. $dataSaldo .' é de R$ '. $valorSaldo .'
</h1>              

<h2>Transações</h2>        
<table border="1" cellpadding="3" cellspacing="0">            
    <thead>                
        <tr>                    
            <th>Data        </th>                    
            <th>Descrição   </th>                    
            <th>Tipo        </th>                    
            <th>Valor       </th>                
        </tr>            
    </thead>            
    <tbody>';

    #Leitura o Array das Transações
    foreach ($ofx->getTransactions() as $transaction) : 
        
    $html .= 
    '<tr>                       
        <td>'. date("Y-m-d", strtotime(substr($transaction->DTPOSTED, 0, 8))) .'</td>                        
        <td>'. $transaction->MEMO       .'</td>                        
        <td>'. $transaction->TRNTYPE    .'</td>                        
        <td>'. $transaction->TRNAMT     .'</td>                    
    </tr>'; 
    endforeach;     
    
    $html .= '</tbody> ';
    
print ($html);
