<?php
// Arquivos necessários
include $_SERVER['DOCUMENT_ROOT'].'/extranet/libs/_conf_x.php';
include $_SERVER['DOCUMENT_ROOT'].'/extranet/libs/class.database.php';
include $_SERVER['DOCUMENT_ROOT'].'/extranet/libs/functions.framework.php';
include $_SERVER['DOCUMENT_ROOT'].'/extranet/callbackse.php';

require_once 'teste/bot/class.whatsapp.php';
                    
$Comunicacao = new WhatsAppMSG;
$tipoRQ = 'POST';

// Cria o objeto de conexao com o banco de dados
$action = new DataBase();
// Conecta no banco de dados
$link = $action -> conecta();
$robot['dataTable'] = 'whatsapp_robot';
$robot['sandboxTestNumber'] = '5511993852020';

//Cria o objeto de envio do whatsapp
$mandaMSGWhats = new RobotPLL();
/*
Chatbot, obtem as informações das OS que estão abertas no sistema.
Recebe as informações enviadas pela Wavy em um callback parar avaliar o bot
*/
#header("Content-type: application/json;charset=utf8");
header("Content-type: application/json");


$form['raw']  = file_get_contents('php://input');

#raw cliente
#$dados['debug']=true;
#$form['raw'] = '{"total":1,"data":[{"id":"ca4f9360-3c7e-11ea-a974-3ae249e5a7c5","source":"5511993852020","origin":"551121057444","userProfile":{"name":"Alan Borim","whatsAppId":"5511993852020"},"messageId":"4f6ea72e-b9ae-4a92-9668-d02ded9b54ca","message":{"type":"TEXT","messageText":"27314763895"},"receivedAt":1579632583000,"receivedDate":"2020-01-21T18:49:43Z","session":{"id":"50642814373440601","createdAt":1579454164227}}],"clientInfo":{"customerId":6539,"subAccountId":11923,"userId":22276}}';

#$text = str_replace('},]',"}]",$raw);
$r = json_decode($form['raw'],true);
$dados = $r['data'][0];
// Verifica se a variável está composta com o mínimo necessário

if (sizeof($dados)==0) {
    die('dados vazios');
}

if($dados['source']!="5511993852020"){
    exit;
}


function buscaOSDados($cpfCnpj,$telefoneCliente,$action,$numOS,$numSinistro){

    $query = "SELECT * FROM os 
    WHERE   cpf_cnpj = '".$action->secure($cpfCnpj)."' 
            AND (tel1 LIKE '%".$action->secure($telefoneCliente)."' OR tel2 LIKE '%".$action->secure($telefoneCliente)."')
            AND TIMESTAMPDIFF(DAY,os.data_inicio,CURRENT_TIMESTAMP()) <=90
    order by os.os desc limit 1";
            $result=$action->query_db($query);
            if ($action->result_quantidade($result) == 0) {
                $erro[] = 'OS não encontrada';
            } 
            else {
                
                $osx=$action->array_db($result)[0];
                print_r($osx);
                // Muda a base da empresa
                $action->muda_db($action->secure('pllb2b_'.$osx['empresa']));
                
                

                    //busca no banco de dados a ultima mensagem enviada pelo cliente
                    $query = "SELECT os.id, os.os_fabricante, cadastro_cliente.cpf_cnpj, cadastro_cliente.nome_razao_social, os_tipo_servico.subtipo, cadastro_cliente.tel1, cadastro_cliente.tel2, aparelho_marca.titulo AS marca, aparelho_modelo.titulo AS modelo, aparelho_modelo.titulo_comercial AS modelo_comercial, os.data_inicio, os.data_checkin, os.callcenter_start, os.data_entrega,
                    round((case when ((os_tipo_servico.garantia_estendida = '1') and (os_tipo_servico.percentual_aparelho > 0) and (os.produto_valor_compra > 0)) then ((((os.produto_valor_compra * os_tipo_servico.percentual_aparelho) / 100) + ((ifnull(orcamento_extra_pagamento.percentual,0) / 100) * ((os.produto_valor_compra * os_tipo_servico.percentual_aparelho) / 100))) - ifnull(orcamento.valor_desconto,0)) when ((os_tipo_servico.garantia_estendida = '1') and (os_tipo_servico.percentual_sinistro > 0)) then ((((((ifnull(orcamento.valor_total_peca,0) - ifnull(orcamento.valor_desconto,0)) + ifnull(orcamento.valor_total_servico,0)) * os_tipo_servico.percentual_sinistro) / 100) + ((((ifnull(orcamento_extra_pagamento.percentual,0) / 100) * (ifnull(orcamento.valor_total_peca,0) + ifnull(orcamento.valor_total_servico,0))) * os_tipo_servico.percentual_sinistro) / 100)) + ifnull(orcamento.valor_total_servico_extra,0)) else ((((ifnull(orcamento.valor_total_peca,0) + ifnull(orcamento.valor_total_servico,0)) - ifnull(orcamento.valor_desconto,0)) + ((ifnull(orcamento_extra_pagamento.percentual,0) / 100) * (((ifnull(orcamento.valor_total_peca,0) + ifnull(orcamento.valor_total_servico,0)) + ifnull(orcamento.valor_total_servico_extra,0)) - ifnull(orcamento.valor_desconto,0)))) + ifnull(orcamento.valor_total_servico_extra,0)) end),2) AS valor_franquia,
                    cadastro_cliente.email, cadastro_cliente.tel1, cadastro_cliente.tel2,
                    UPPER(os_tipo_servico_categoria.titulo) AS parceiro, UPPER(os_tipo_atendimento.titulo) AS tipo_atendimento,
                        NOW() AS adicionado,
                    case when (os_tipo_servico.subtipo = 'QA') AND (os.data_pagamento IS NULL) AND (os.data_entrega IS NOT NULL) AND (orcamento.fk_status = 2) AND (orcamento.pago_total = '0') then CONCAT('Identificamos que sua Ordem de Serviço está com pendência de pagamento no valor de R$ ', round((case when ((os_tipo_servico.garantia_estendida = '1') and (os_tipo_servico.percentual_aparelho > 0) and (os.produto_valor_compra > 0)) then ((((os.produto_valor_compra * os_tipo_servico.percentual_aparelho) / 100) + ((ifnull(orcamento_extra_pagamento.percentual,0) / 100) * ((os.produto_valor_compra * os_tipo_servico.percentual_aparelho) / 100))) - ifnull(orcamento.valor_desconto,0)) when ((os_tipo_servico.garantia_estendida = '1') and (os_tipo_servico.percentual_sinistro > 0)) then ((((((ifnull(orcamento.valor_total_peca,0) - ifnull(orcamento.valor_desconto,0)) + ifnull(orcamento.valor_total_servico,0)) * os_tipo_servico.percentual_sinistro) / 100) + ((((ifnull(orcamento_extra_pagamento.percentual,0) / 100) * (ifnull(orcamento.valor_total_peca,0) + ifnull(orcamento.valor_total_servico,0))) * os_tipo_servico.percentual_sinistro) / 100)) + ifnull(orcamento.valor_total_servico_extra,0)) else ((((ifnull(orcamento.valor_total_peca,0) + ifnull(orcamento.valor_total_servico,0)) - ifnull(orcamento.valor_desconto,0)) + ((ifnull(orcamento_extra_pagamento.percentual,0) / 100) * (((ifnull(orcamento.valor_total_peca,0) + ifnull(orcamento.valor_total_servico,0)) + ifnull(orcamento.valor_total_servico_extra,0)) - ifnull(orcamento.valor_desconto,0)))) + ifnull(orcamento.valor_total_servico_extra,0)) end),2) ,' acesse o link para efetuar o pagamento e liberar seu celular para reparo: [LINK_PAG]')
                    when (os_tipo_servico.subtipo = 'QA') AND (os.data_pagamento IS NOT NULL) AND (os.data_entrega IS NOT NULL) AND (orcamento.fk_status = 2) AND (orcamento.pago_total = '1') then 'Pago'
                    ELSE 'Não Autorizado ou já Pago' END AS franquia_paga,
                    case when (os_tipo_servico.subtipo = 'QA') AND (expedicao.fk_tipo = 2) AND (expedicao.objeto IS NOT NULL) AND (os.data_entrega IS NOT NULL) 
                    then 'Celular Entregue' 
                    ELSE 'Celular ainda não foi entregue ou foi cancelado' END AS OS_Correios,  
                    case when (os_tipo_servico.subtipo = 'QA') AND (expedicao.fk_tipo = 2) then 'P' ELSE 'NP' END AS Pago,
                    setor.titulo AS setor, setor_status.titulo AS setor_status,
                    case when (aparelho_marca.titulo = 'APPLE') AND (setor_status.codigo = 'ICOUA' OR 'IACCS' OR 'IACSS' OR 'SAADI' OR 'CHEIA') AND (os.data_entrega IS NOT NULL) then 'O seu iphone possui o recurso buscar iPhone ativo. É necessario realizar a desativação do iCloud para realizarmos o reparo' 
                    when (aparelho_marca.titulo = 'APPLE') AND (os.data_entrega IS NOT NULL) then 'aparelho APPLE já entregue'
                    when (aparelho_marca.titulo = 'APPLE') AND (os.data_entrega IS NULL) then 'iCloud'
                    ELSE 'NiCloud' END AS iCloud, 
                    
                    #setores
                    case when (setor.codigo = 'PREOS') AND (os.data_checkin IS NOT NULL) AND (cr_recebimento.id IS NULL) then 'Aguardando Recebimento' 
                    when (setor.codigo IN ('TRIAG','RESTA','TRIDI')) AND (cr_recebimento.id IS NOT NULL) AND (os.data_checkin IS NOT NULL)  then 'Celular recebido para Reparo' 
                    when (setor.codigo IN ('BLABO','REPAR','REPN3','ANPEC')) AND (cr_recebimento.id IS NOT NULL) AND (os.data_checkin IS NOT NULL)  then 'Celular em Reparo' 
                    when (setor.codigo IN ('DESTO')) AND (cr_recebimento.id IS NOT NULL) AND (os.data_checkin IS NOT NULL)  then 'Celular em Reparo'
                    when (setor.codigo IN ('EDIGI')) AND (cr_recebimento.id IS NOT NULL) AND (os.data_checkin IS NOT NULL)  then 'Celular Recebido para Reparo' 
                    when (os.data_checkin IS NULL) AND (cr_recebimento.id IS NOT NULL) then 'Celular Recebido para Reparo' 
                    when (setor.codigo IN('DIAGN')) AND (cr_recebimento.id IS NOT NULL) AND (os.data_checkin IS NOT NULL) then 'Celular em Reparo' 
                    when (setor.codigo IN('LAUDO')) AND (cr_recebimento.id IS NOT NULL) AND (os.data_checkin IS NOT NULL) then 'Celular em Reparo' 
                    when (setor.codigo IN('CORCA')) AND (cr_recebimento.id IS NOT NULL) AND (os.data_checkin IS NOT NULL) then 'Celular em Reparo'
                    END AS status_aparelho,
                    
                    #aparelho já expedido
                    expedicao.objeto,
                    case when ((cr_recebimento.id IS NOT NULL) AND (os.data_checkin IS NOT NULL) AND (setor.codigo IN ('CHECK','GEXPE')) OR (setor_status.codigo IN('ENTCO')) AND 
                    (expedicao.fk_tipo = 2) AND (expedicao.objeto IS NOT NULL)) then CONCAT ('Celular Enviado ao Consumidor. O seu código de rastreio é ', expedicao.objeto,' acompanhe pelo link https:// www2.correios.com.br/sistemas/rastreamento/default.cfm')
                    when ((cr_recebimento.id IS NOT NULL) AND (os.data_checkin IS NOT NULL) AND (setor.codigo IN('CHECK')) AND (expedicao.fk_tipo = 2) AND 
                    (expedicao.objeto IS NULL)) then 'Celular Em Processo de Envio, retorne em breve para obter seu código de rastreio.'
                    ELSE 'Aparelho ainda em reparo ou cancelado' END AS codigo_rastreio
                    
                    FROM os 
                    LEFT JOIN os_tipo_servico ON os_tipo_servico.id = os.fk_os_tipo_servico
                    LEFT JOIN setor_status ON setor_status.id = os.fk_status
                    LEFT JOIN setor ON setor.id = setor_status.fk_setor
                    LEFT JOIN sigep_reverso ON sigep_reverso.fk_os = os.id AND sigep_reverso.descricao_erro IS NULL
                    LEFT JOIN cr_recebimento ON cr_recebimento.fk_os = os.id
                    LEFT JOIN cadastro_cliente ON cadastro_cliente.id = os.fk_cliente
                    LEFT JOIN orcamento ON orcamento.fk_os = os.id
                    LEFT JOIN orcamento_extra_pagamento ON orcamento.fk_extra_pagamento = orcamento_extra_pagamento.id
                    LEFT JOIN orcamento_status ON orcamento.fk_status = orcamento_status.id
                    LEFT JOIN expedicao ON expedicao.fk_os = os.id
                    LEFT JOIN aparelho_modelo ON os.fk_modelo = aparelho_modelo.id
                    LEFT JOIN aparelho_marca ON aparelho_modelo.fk_marca = aparelho_marca.codigo
                    LEFT JOIN os_tipo_servico_categoria ON os_tipo_servico.fk_categoria = os_tipo_servico_categoria.id
                    LEFT JOIN os_tipo_atendimento ON os.fk_os_tipo_atendimento = os_tipo_atendimento.id
                    WHERE   cadastro_cliente.cpf_cnpj = '".$action->secure($cpfCnpj)."' 
                            AND (cadastro_cliente.tel1 LIKE '%".$action->secure($telefoneCliente)."' OR cadastro_cliente.tel2 LIKE '%".$action->secure($busca['telefone'])."')
                            AND TIMESTAMPDIFF(DAY,os.data_inicio,CURRENT_TIMESTAMP()) <=90
                            order by os.id desc limit 1";

                    $result=$action->query_db($query);
                    $res=$action->array_db($result)[0];
                    echo $action->result_quantidade($res);
                    if(($res['subtipo']=='QA'||$res['subtipo']=='GE')&&($res['Pago']=='P')&&($res['iCloud']=='NiCloud')&&($res['OS_Correios']=='Celular Entregue')){
                        $res['saida'] = 'OSQACORREIOS';
                    }elseif(($res['subtipo']=='QA'||$res['subtipo']=='GE')&&($res['Pago']=='NP')&&($res['iCloud']=='iCloud')){
                        $res['saida'] = 'OSICLOUDNP';
                    }elseif(($res['subtipo']=='QA'||$res['subtipo']=='GE')&&($res['Pago']=='P')&&($res['iCloud']=='iCloud')){
                        $res['saida'] = 'OSICLOUD';
                    }elseif(($res['subtipo']=='QA'||$res['subtipo']=='GE')&&($res['Pago']=='NP')&&($res['iCloud']=='NiCloud')&&($res['setor']=='PRÉ-OS')){
                        $res['saida'] = 'OSPREOSNP';
                    }elseif(($res['subtipo']=='QA'||$res['subtipo']=='GE')&&($res['Pago']=='P')&&($res['iCloud']=='NiCloud')&&($res['setor']=='PRÉ-OS')){
                        $res['saida'] = 'OSPREOS';
                    }elseif(($res['subtipo']=='QA'||$res['subtipo']=='GE')&&($res['Pago']=='NP')&&($res['iCloud']=='NiCloud')&&($res['setor']=='CHECKIN')){
                        $res['saida'] = 'OSCHECKINNP';
                    }elseif(($res['subtipo']=='QA'||$res['subtipo']=='GE')&&($res['Pago']=='P')&&($res['iCloud']=='NiCloud')&&($res['setor']=='CHECKIN')){
                        $res['saida'] = 'OSCHECKIN';
                    }elseif(($res['subtipo']=='QA'||$res['subtipo']=='GE')&&($res['Pago']=='NP')&&($res['iCloud']=='NiCloud')&&($res['setor']=='TRIAGEM')){
                        $res['saida'] = 'OSTRIAGEMNP';
                    }elseif(($res['subtipo']=='QA'||$res['subtipo']=='GE')&&($res['Pago']=='P')&&($res['iCloud']=='NiCloud')&&($res['setor']=='TRIAGEM')){
                        $res['saida'] = 'OSTRIAGEM';
                    }elseif(($res['subtipo']=='QA'||$res['subtipo']=='GE')&&($res['Pago']=='NP')&&($res['iCloud']=='NiCloud')&&($res['setor']=='DIAGNÓSTICO')){
                        $res['saida'] = 'OSDIAGNP';
                    }elseif(($res['subtipo']=='QA'||$res['subtipo']=='GE')&&($res['Pago']=='P')&&($res['iCloud']=='NiCloud')&&($res['setor']=='DIAGNÓSTICO')){
                        $res['saida'] = 'OSDIAG';
                    }elseif(($res['subtipo']=='QA'||$res['subtipo']=='GE')&&($res['Pago']=='NP')&&($res['iCloud']=='NiCloud')&&($res['setor']=='LABORATÓRIO')){
                        $res['saida'] = 'OSLABNP';
                    }elseif(($res['subtipo']=='QA'||$res['subtipo']=='GE')&&($res['Pago']=='P')&&($res['iCloud']=='NiCloud')&&($res['setor']=='LABORATÓRIO')){
                        $res['saida'] = 'OSLAB';
                    }elseif(($res['subtipo']=='QA'||$res['subtipo']=='GE')&&($res['Pago']=='NP')&&($res['iCloud']=='NiCloud')&&($res['setor']=='ESTOQUE')){
                        $res['saida'] = 'OSESTOQUENP';
                    }elseif(($res['subtipo']=='QA'||$res['subtipo']=='GE')&&($res['Pago']=='P')&&($res['iCloud']=='NiCloud')&&($res['setor']=='ESTOQUE')){
                        $res['saida'] = 'OSESTOQUE';
                    }elseif(($res['subtipo']=='QA'||$res['subtipo']=='GE')&&($res['Pago']=='NP')&&($res['iCloud']=='NiCloud')&&($res['setor']=='ORÇAMENTO')){
                        $res['saida'] = 'OSORCNP';
                    }elseif(($res['subtipo']=='QA'||$res['subtipo']=='GE')&&($res['Pago']=='P')&&($res['iCloud']=='NiCloud')&&($res['setor']=='ORÇAMENTO')){
                        $res['saida'] = 'OSORC';
                    }elseif(($res['subtipo']=='QA'||$res['subtipo']=='GE')&&($res['Pago']=='NP')&&($res['iCloud']=='NiCloud')&&($res['setor']=='QUALIDADE')){
                        $res['saida'] = 'OSCQNP';
                    }elseif(($res['subtipo']=='QA'||$res['subtipo']=='GE')&&($res['Pago']=='P')&&($res['iCloud']=='NiCloud')&&($res['setor']=='QUALIDADE')){
                        $res['saida'] = 'OSCQ';
                    }elseif(($res['subtipo']=='QA'||$res['subtipo']=='GE')&&($res['Pago']=='NP')&&($res['iCloud']=='NiCloud')&&($res['setor']=='CHECKOUT')&&($res['objeto']=='')){
                        $res['saida'] = 'OSCQNAONP';
                    }elseif(($res['subtipo']=='QA'||$res['subtipo']=='GE')&&($res['Pago']=='P')&&($res['iCloud']=='NiCloud')&&($res['setor']=='CHECKOUT')&&($res['objeto']=='')){
                        $res['saida'] = 'OSCQNAO';
                    }elseif(($res['subtipo']=='QA'||$res['subtipo']=='GE')&&($res['Pago']=='NP')&&($res['iCloud']=='NiCloud')&&($res['setor']=='CHECKOUT')&&($res['objeto']!='')){
                        $res['saida'] = 'OSCQSIMNP';
                    }elseif(($res['subtipo']=='QA'||$res['subtipo']=='GE')&&($res['Pago']=='P')&&($res['iCloud']=='NiCloud')&&($res['setor']=='CHECKOUT')&&($res['objeto']!='')){
                        $res['saida'] = 'OSCQSIM';
                    }
                    
                    return $res;
            }
}

#print_r($dados);
#print_r(buscaOSDados($dados['message']['messageText'],substr($robot['sandboxTestNumber'],-8),$action,"",""));

#exit;

$form['tipoAtendimento']        = 'BOT';
$form['mid']                    = $dados['id'];
$form['source']                 = $dados['source'];
$form['origin']                 = $dados['origin'];
$form['userProfile_name']       = $dados['userProfile']['name'];
$form['userProfile_whatsappId'] = $dados['userProfile']['whatsAppId'];
$form['message_type']           = $dados['message']['type'];
$form['message_messageText']    = $dados['message']['messageText'];
$form['receivedAt']             = $dados['receivedAt'];
$form['session_id']             = $dados['session']['id'];
$form['session_createdAt']      = $dados['session']['createdAt'];

// Segunda fase, retorno de status de envio
$form['destination']            = $dados['destination'];
$form['sent']                   = $dados['sent'];
$form['sentStatusCode']         = $dados['sentStatusCode'];
$form['sentStatus']             = $dados['sentStatus'];
$form['sentAt']                 = $dados['sentAt'];
$form['updatedAt']              = $dados['updatedAt'];
// Terceira fase, chegada de mensagem ao destinatário
$form['delivered']              = $dados['delivered'];
$form['deliveredStatusCode']    = $dados['deliveredStatusCode'];
$form['deliveredStatus']        = $dados['deliveredStatus'];
$form['deliveredAt']            = $dados['deliveredAt'];
// Quarta fase, leitura da mensagem
$form['read']                   = $dados['read'];
$form['readAt']                 = $dados['readAt'];


/*carrega as mensagens do chatbot */
//carrega todas as mensagens do banco de dados.
$query_mensagens  = "select * from whatsapp_msg";
$resultado        = $action->query_db($query_mensagens);
$msg              = $action->array_db($resultado);

//busca no banco de dados a ultima mensagem enviada pelo cliente
$query = 'SELECT * FROM whatsapp_robot WHERE SOURCE LIKE "%'.substr($robot['sandboxTestNumber'],-8).'" ORDER BY id DESC limit 1';
$result=$action->query_db($query);
$r=$action->array_db($result);

/*
DEBUG DAS MENSAGENS DE ENTRADA
print_r($msg);
print_r($r);
print_r($form);
*/
$passo = buscaOSDados($dados['message']['messageText'],substr($robot['sandboxTestNumber'],-8),$action,"","");
/* Antes de qualquer coisa deve-se verificar se a mensagem vem do cliente ou vem da PLL */
if(strlen(trim(onlynumbers($dados['message']['messageText'])))==9){
    $passo = buscaOSDados($dados['message']['messageText'],substr($robot['sandboxTestNumber'],-8),$action);
}
//Aqui inicia o bot caso a mensagem seja maior que 1 caractere e o checkpoint for igual a nada ou igual a FINALIZADO abre um novo atendimento com o BOASVINDAS
if((!empty($form['source']))&&(($r[0]['checkpoint']=="")||($r[0]['checkpoint']=="FINALIZADO"))){
    #somente boas vindas
    $form['checkpoint'] = 'BOASVINDAS';
    $mandaMSGWhats->enviaWhats($robot['sandboxTestNumber'],utf8_encode($msg[40]['mensagem']),$form['checkpoint']);
    $mandaMSGWhats->enviaWhats($robot['sandboxTestNumber'],utf8_encode($msg[0]['mensagem']),$form['checkpoint']); 
}else{
    //validação de cpf caso exista OK caso não exista ou digitado errado retorna opção inválida
    if (((strlen($form['message_messageText'])>1)&&(!empty($form['source'])))&&((valida_cpf(trim(onlynumbers($form['message_messageText']))) == true) || (valida_cnpj(trim(onlynumbers($form['message_messageText']))) == true))) {   
        if(!empty($passo)){
            $form['documento'] = '1';
            $form['checkpoint'] = 'VERDOC';
            $mandaMSGWhats->enviaWhats($robot['sandboxTestNumber'],utf8_encode(str_replace("@@CLIENTE_NOME@@",$passo['nome_razao_social'],$msg[1]['mensagem'])),$form['checkpoint']);
            #$mandaMSGWhats->enviaWhats($robot['sandboxTestNumber'],utf8_encode($msg[2]['mensagem']),$form['checkpoint']);
        
            #OS Serviço QA Franquia Paga Entregue correios
            if($form['message_messageText']=='OSQACORREIOS'){
                $form['checkpoint']='OS';
                $mandaMSGWhats->enviaWhats($robot['sandboxTestNumber'],utf8_encode($msg[24]['mensagem']),$form['checkpoint']);
                
                $mandaMSGWhats->enviaWhats($robot['sandboxTestNumber'],utf8_encode($msg[2]['mensagem']),$form['checkpoint']);
            }
            #OS iClous Ativo
            elseif($form['message_messageText']=='OSICLOUD'){
                $form['checkpoint']='OS';
                $mandaMSGWhats->enviaWhats($robot['sandboxTestNumber'],utf8_encode($msg[25]['mensagem']),$form['checkpoint']);
                
                $mandaMSGWhats->enviaWhats($robot['sandboxTestNumber'],utf8_encode($msg[2]['mensagem']),$form['checkpoint']);
            }#OS iClous Ativo Franquia não paga
            #elseif($form['message_messageText']=='OSICLOUD'){
            elseif($passo['saida']=='OSICLOUDNP'){
                $form['checkpoint']='OS';
                $mandaMSGWhats->enviaWhats($robot['sandboxTestNumber'],str_replace("@@VALOR_FRANQ@@",$passo['valor_franquia'],utf8_encode($msg[23]['mensagem'])),$form['checkpoint']);
                usleep(200000);
                $mandaMSGWhats->enviaWhats($robot['sandboxTestNumber'],utf8_encode($msg[25]['mensagem']),$form['checkpoint']);
                usleep(200000);
                $mandaMSGWhats->enviaWhats($robot['sandboxTestNumber'],utf8_encode($msg[2]['mensagem']),$form['checkpoint']);
            }#OS Pré-OS
            #elseif($form['message_messageText']=='OSPREOS'){
            elseif($passo['saida']=='OSPREOS'){
                $form['checkpoint']='OS';
                $mandaMSGWhats->enviaWhats($robot['sandboxTestNumber'],utf8_encode($msg[26]['mensagem']),$form['checkpoint']);
                usleep(200000);
                $mandaMSGWhats->enviaWhats($robot['sandboxTestNumber'],utf8_encode($msg[2]['mensagem']),$form['checkpoint']);
            }#OS Pré-OS Franquia não paga
            elseif($passo['saida']=='OSPREOSNP'){
                $form['checkpoint']='OS';
                $mandaMSGWhats->enviaWhats($robot['sandboxTestNumber'],str_replace("@@VALOR_FRANQ@@",$passo['valor_franquia'],utf8_encode($msg[23]['mensagem'])),$form['checkpoint']);
                usleep(200000);
                $mandaMSGWhats->enviaWhats($robot['sandboxTestNumber'],utf8_encode($msg[26]['mensagem']),$form['checkpoint']);
                usleep(200000);
                $mandaMSGWhats->enviaWhats($robot['sandboxTestNumber'],utf8_encode($msg[2]['mensagem']),$form['checkpoint']);
            }#OS Triagem
            #elseif($form['message_messageText']=='OSTRIAGEM'){
            elseif($passo['saida']=='OSTRIAGEM'){
                $form['checkpoint']='OS';
                $mandaMSGWhats->enviaWhats($robot['sandboxTestNumber'],utf8_encode($msg[28]['mensagem']),$form['checkpoint']);
                usleep(200000);
                $mandaMSGWhats->enviaWhats($robot['sandboxTestNumber'],utf8_encode($msg[2]['mensagem']),$form['checkpoint']);
            }#OS Triagem Franquia não paga
            elseif($passo['saida']=='OSTRIAGEMNP'){
                $form['checkpoint']='OS';
                $mandaMSGWhats->enviaWhats($robot['sandboxTestNumber'],str_replace("@@VALOR_FRANQ@@",$passo['valor_franquia'],utf8_encode($msg[23]['mensagem'])),$form['checkpoint']);
                usleep(200000);
                $mandaMSGWhats->enviaWhats($robot['sandboxTestNumber'],utf8_encode($msg[28]['mensagem']),$form['checkpoint']);
                usleep(200000);
                $mandaMSGWhats->enviaWhats($robot['sandboxTestNumber'],utf8_encode($msg[2]['mensagem']),$form['checkpoint']);
            }#OS LABORATORIO
            #elseif($form['message_messageText']=='OSLAB'){
            elseif($passo['saida']=='OSLAB'){
                $form['checkpoint']='OS';
                $mandaMSGWhats->enviaWhats($robot['sandboxTestNumber'],utf8_encode($msg[30]['mensagem']),$form['checkpoint']);
                usleep(200000);
                $mandaMSGWhats->enviaWhats($robot['sandboxTestNumber'],utf8_encode($msg[2]['mensagem']),$form['checkpoint']);
            }#OS LABORATORIO Franquia não paga
            elseif($passo['saida']=='OSLABNP'){
                $form['checkpoint']='OS';
                $mandaMSGWhats->enviaWhats($robot['sandboxTestNumber'],str_replace("@@VALOR_FRANQ@@",$passo['valor_franquia'],utf8_encode($msg[23]['mensagem'])),$form['checkpoint']);
                usleep(200000);
                $mandaMSGWhats->enviaWhats($robot['sandboxTestNumber'],utf8_encode($msg[30]['mensagem']),$form['checkpoint']);
                usleep(200000);
                $mandaMSGWhats->enviaWhats($robot['sandboxTestNumber'],utf8_encode($msg[2]['mensagem']),$form['checkpoint']);
            }#OS ESTOQUE
            #elseif($form['message_messageText']=='OSESTOQUE'){
            elseif($passo['saida']=='OSESTOQUE'){
                $form['checkpoint']='OS';
                $mandaMSGWhats->enviaWhats($robot['sandboxTestNumber'],utf8_encode($msg[32]['mensagem']),$form['checkpoint']);
                usleep(200000);
                $mandaMSGWhats->enviaWhats($robot['sandboxTestNumber'],utf8_encode($msg[2]['mensagem']),$form['checkpoint']);
            }#OS ESTOQUE Franquia não paga
            elseif($passo['saida']=='OSESTOQUENP'){
                $form['checkpoint']='OS';
                $mandaMSGWhats->enviaWhats($robot['sandboxTestNumber'],str_replace("@@VALOR_FRANQ@@",$passo['valor_franquia'],utf8_encode($msg[23]['mensagem'])),$form['checkpoint']);
                usleep(200000);
                $mandaMSGWhats->enviaWhats($robot['sandboxTestNumber'],utf8_encode($msg[32]['mensagem']),$form['checkpoint']);
                usleep(200000);
                $mandaMSGWhats->enviaWhats($robot['sandboxTestNumber'],utf8_encode($msg[2]['mensagem']),$form['checkpoint']);
            }#OS CQ
            #elseif($form['message_messageText']=='OSCQ'){
            elseif($passo['saida']=='OSCQ'){
                $form['checkpoint']='OS';
                $mandaMSGWhats->enviaWhats($robot['sandboxTestNumber'],utf8_encode($msg[34]['mensagem']),$form['checkpoint']);
                usleep(200000);
                $mandaMSGWhats->enviaWhats($robot['sandboxTestNumber'],utf8_encode($msg[2]['mensagem']),$form['checkpoint']);
            }#OS CQ Franquia não paga
            elseif($passo['saida']=='OSCQNP'){
                $form['checkpoint']='OS';
                $mandaMSGWhats->enviaWhats($robot['sandboxTestNumber'],str_replace("@@VALOR_FRANQ@@",$passo['valor_franquia'],utf8_encode($msg[23]['mensagem'])),$form['checkpoint']);
                usleep(200000);
                $mandaMSGWhats->enviaWhats($robot['sandboxTestNumber'],utf8_encode($msg[34]['mensagem']),$form['checkpoint']);
                usleep(200000);
                $mandaMSGWhats->enviaWhats($robot['sandboxTestNumber'],utf8_encode($msg[2]['mensagem']),$form['checkpoint']);
            }#OS CHEKIN
            #elseif($form['message_messageText']=='OSCHECKIN'){
            elseif($passo['saida']=='OSCHECKIN'){
                $form['checkpoint']='OS';
                $mandaMSGWhats->enviaWhats($robot['sandboxTestNumber'],utf8_encode($msg[27]['mensagem']),$form['checkpoint']);
                usleep(200000);
                $mandaMSGWhats->enviaWhats($robot['sandboxTestNumber'],utf8_encode($msg[2]['mensagem']),$form['checkpoint']);
            }#OS CHEKIN Franquia não paga
            elseif($passo['saida']=='OSCHECKINNP'){
                $form['checkpoint']='OS';
                $mandaMSGWhats->enviaWhats($robot['sandboxTestNumber'],str_replace("@@VALOR_FRANQ@@",$passo['valor_franquia'],utf8_encode($msg[23]['mensagem'])),$form['checkpoint']);
                usleep(200000);
                $mandaMSGWhats->enviaWhats($robot['sandboxTestNumber'],utf8_encode($msg[27]['mensagem']),$form['checkpoint']);
                usleep(200000);
                $mandaMSGWhats->enviaWhats($robot['sandboxTestNumber'],utf8_encode($msg[2]['mensagem']),$form['checkpoint']);
            }#OS CQ COM OBJETO CORREIOS
            #elseif($form['message_messageText']=='OSCQSIM'){
            elseif($passo['saida']=='OSCQSIM'){
                $form['checkpoint']='OS';
                $mandaMSGWhats->enviaWhats($robot['sandboxTestNumber'],utf8_encode($msg[35]['mensagem']),$form['checkpoint']);
                usleep(200000);
                $mandaMSGWhats->enviaWhats($robot['sandboxTestNumber'],utf8_encode($msg[2]['mensagem']),$form['checkpoint']);
            }#OS CQ COM OBJETO CORREIOS Franquia não Paga
            elseif($passo['saida']=='OSCQSIMNP'){
                $form['checkpoint']='OS';
                $mandaMSGWhats->enviaWhats($robot['sandboxTestNumber'],str_replace("@@VALOR_FRANQ@@",$passo['valor_franquia'],utf8_encode($msg[23]['mensagem'])),$form['checkpoint']);
                usleep(200000);
                $mandaMSGWhats->enviaWhats($robot['sandboxTestNumber'],utf8_encode($msg[35]['mensagem']),$form['checkpoint']);
                usleep(200000);
                $mandaMSGWhats->enviaWhats($robot['sandboxTestNumber'],utf8_encode($msg[2]['mensagem']),$form['checkpoint']);
            }#OS CQ SEM OBJETO CORREIOS
            #elseif($form['message_messageText']=='OSCQNAO'){
            elseif($passo['saida']=='OSCQNAO'){
                $form['checkpoint']='OS';
                $mandaMSGWhats->enviaWhats($robot['sandboxTestNumber'],utf8_encode($msg[36]['mensagem']),$form['checkpoint']);
                usleep(200000);
                $mandaMSGWhats->enviaWhats($robot['sandboxTestNumber'],utf8_encode($msg[2]['mensagem']),$form['checkpoint']);
            }#OS CQ SEM OBJETO CORREIOS Franquia não paga
            elseif($passo['saida']=='OSCQNAONP'){
                $form['checkpoint']='OS';
                $mandaMSGWhats->enviaWhats($robot['sandboxTestNumber'],str_replace("@@VALOR_FRANQ@@",$passo['valor_franquia'],utf8_encode($msg[23]['mensagem'])),$form['checkpoint']);
                usleep(200000);
                $mandaMSGWhats->enviaWhats($robot['sandboxTestNumber'],utf8_encode($msg[36]['mensagem']),$form['checkpoint']);
                usleep(200000);
                $mandaMSGWhats->enviaWhats($robot['sandboxTestNumber'],utf8_encode($msg[2]['mensagem']),$form['checkpoint']);
            }#OS DIAGNOSTICO
            #elseif($form['message_messageText']=='OSDIAG'){
            elseif($passo['saida']=='OSDIAG'){
                $form['checkpoint']='OS';
                $mandaMSGWhats->enviaWhats($robot['sandboxTestNumber'],utf8_encode($msg[29]['mensagem']),$form['checkpoint']);
                usleep(200000);
                $mandaMSGWhats->enviaWhats($robot['sandboxTestNumber'],utf8_encode($msg[2]['mensagem']),$form['checkpoint']);
            }#OS DIAGNOSTICO Franquia não paga
            elseif($passo['saida']=='OSDIAGNP'){
                $form['checkpoint']='OS';
                $mandaMSGWhats->enviaWhats($robot['sandboxTestNumber'],str_replace("@@VALOR_FRANQ@@",$passo['valor_franquia'],utf8_encode($msg[23]['mensagem'])),$form['checkpoint']);
                usleep(200000);
                $mandaMSGWhats->enviaWhats($robot['sandboxTestNumber'],utf8_encode($msg[29]['mensagem']),$form['checkpoint']);
                usleep(200000);
                $mandaMSGWhats->enviaWhats($robot['sandboxTestNumber'],utf8_encode($msg[2]['mensagem']),$form['checkpoint']);
            }#OS ORCAMENTO
            #elseif($form['message_messageText']=='OSORC'){
            elseif($passo['saida']=='OSORC'){
                $form['checkpoint']='OS';
                $mandaMSGWhats->enviaWhats($robot['sandboxTestNumber'],utf8_encode($msg[33]['mensagem']),$form['checkpoint']);
                usleep(200000);
                $mandaMSGWhats->enviaWhats($robot['sandboxTestNumber'],utf8_encode($msg[2]['mensagem']),$form['checkpoint']);
            }#OS ORCAMENTO Franquia não paga
            elseif($passo['saida']=='OSORCNP'){
                $form['checkpoint']='OS';
                $mandaMSGWhats->enviaWhats($robot['sandboxTestNumber'],str_replace("@@VALOR_FRANQ@@",$passo['valor_franquia'],utf8_encode($msg[23]['mensagem'])),$form['checkpoint']);
                usleep(200000);
                $mandaMSGWhats->enviaWhats($robot['sandboxTestNumber'],utf8_encode($msg[33]['mensagem']),$form['checkpoint']);
                usleep(200000);
                $mandaMSGWhats->enviaWhats($robot['sandboxTestNumber'],utf8_encode($msg[2]['mensagem']),$form['checkpoint']);
            }
    
        }
        
    }#MENU OPÇÕES
    elseif((strlen(trim(onlynumbers($form['message_messageText'])))==1)&&($form['message_messageText']==1)&&(($r[0]['checkpoint']=='OS')||($r[0]['checkpoint']=='VERDOC')||($r[0]['checkpoint']=='ENDERECOSIN')||($r[0]['checkpoint']=='RETIRADAIN')||($r[0]['checkpoint']=='MENUOPCOESIN')||($r[0]['checkpoint']=='OSCHECK')||($r[0]['checkpoint']=='SINISCHECK'))){
        $form['checkpoint']='MENUOPCOES';
        $mandaMSGWhats->enviaWhats($robot['sandboxTestNumber'],utf8_encode($msg[10]['mensagem']),$form['checkpoint']);
        
    }#MENU OPÇÃO 1 
    elseif((strlen(trim(onlynumbers($form['message_messageText'])))==1)&&($form['message_messageText']==1)&&($r[0]['checkpoint']=='MENUOPCOES')){
        $form['checkpoint']='OSCHECK';

        $mandaMSGWhats->enviaWhats($robot['sandboxTestNumber'],utf8_encode("--".$passo['cpf_cnpj']),$form['checkpoint']);
        $mandaMSGWhats->enviaWhats($robot['sandboxTestNumber'],utf8_encode($msg[37]['mensagem']),$form['checkpoint']);
        $mandaMSGWhats->enviaWhats($robot['sandboxTestNumber'],utf8_encode($msg[2]['mensagem']),$form['checkpoint']);
    }#MENU OPÇÃO 2 
    elseif((strlen(trim(onlynumbers($form['message_messageText'])))==1)&&($form['message_messageText']==2)&&($r[0]['checkpoint']=='MENUOPCOES')){
        $form['checkpoint']='SINISCHECK';
        $mandaMSGWhats->enviaWhats($robot['sandboxTestNumber'],utf8_encode($msg[38]['mensagem']),$form['checkpoint']);
        $mandaMSGWhats->enviaWhats($robot['sandboxTestNumber'],utf8_encode($msg[2]['mensagem']),$form['checkpoint']);
    }#MENU OPÇÃO 3 
    elseif((strlen(trim(onlynumbers($form['message_messageText'])))==1)&&($form['message_messageText']==3)&&($r[0]['checkpoint']=='MENUOPCOES')){
        $form['checkpoint']='MENUOPCOESIN';
        $mandaMSGWhats->enviaWhats($robot['sandboxTestNumber'],utf8_encode($msg[11]['mensagem']),$form['checkpoint']);
        $mandaMSGWhats->enviaWhats($robot['sandboxTestNumber'],utf8_encode($msg[2]['mensagem']),$form['checkpoint']);
    }#MENU OPÇÃO 4 
    elseif((strlen(trim(onlynumbers($form['message_messageText'])))==1)&&($form['message_messageText']==4)&&($r[0]['checkpoint']=='MENUOPCOES')){
        $form['checkpoint']='ENDERECOS';
        $mandaMSGWhats->enviaWhats($robot['sandboxTestNumber'],utf8_encode($msg[14]['mensagem']),$form['checkpoint']);
        $mandaMSGWhats->enviaWhats($robot['sandboxTestNumber'],utf8_encode($msg[15]['mensagem']),$form['checkpoint']);
        
    }#MENU OPÇÃO 5 
    elseif((strlen(trim(onlynumbers($form['message_messageText'])))==1)&&($form['message_messageText']==5)&&($r[0]['checkpoint']=='MENUOPCOES')){
        $form['checkpoint']='RETIRADA';
        $mandaMSGWhats->enviaWhats($robot['sandboxTestNumber'],utf8_encode($msg[13]['mensagem']),$form['checkpoint']);
        
    }#MENU OPÇÃO 6 
    elseif((strlen(trim(onlynumbers($form['message_messageText'])))==1)&&($form['message_messageText']==6)&&($r[0]['checkpoint']=='MENUOPCOES')){
        $form['checkpoint']='MENUOPCOESIN';
        $mandaMSGWhats->enviaWhats($robot['sandboxTestNumber'],utf8_encode($msg[22]['mensagem']),$form['checkpoint']);
        $mandaMSGWhats->enviaWhats($robot['sandboxTestNumber'],utf8_encode($msg[2]['mensagem']),$form['checkpoint']);
    }#MENU OPÇÃO 4 - ENDERECOS OPÇÃO 1 
    elseif((strlen(trim(onlynumbers($form['message_messageText'])))==1)&&($form['message_messageText']==1)&&($r[0]['checkpoint']=='ENDERECOS')){
        $form['checkpoint']='ENDERECOSIN';
        $mandaMSGWhats->enviaWhats($robot['sandboxTestNumber'],utf8_encode($msg[16]['mensagem']),$form['checkpoint']);
        $mandaMSGWhats->enviaWhats($robot['sandboxTestNumber'],utf8_encode($msg[2]['mensagem']),$form['checkpoint']);
        
    }#MENU OPÇÃO 4 - ENDERECOS OPÇÃO 2 
    elseif((strlen(trim(onlynumbers($form['message_messageText'])))==1)&&($form['message_messageText']==2)&&($r[0]['checkpoint']=='ENDERECOS')){
        $form['checkpoint']='ENDERECOSIN';
        $mandaMSGWhats->enviaWhats($robot['sandboxTestNumber'],utf8_encode($msg[17]['mensagem']),$form['checkpoint']);
        $mandaMSGWhats->enviaWhats($robot['sandboxTestNumber'],utf8_encode($msg[2]['mensagem']),$form['checkpoint']);
        
    }#MENU OPÇÃO 4 - ENDERECOS OPÇÃO 3 
    elseif((strlen(trim(onlynumbers($form['message_messageText'])))==1)&&($form['message_messageText']==3)&&($r[0]['checkpoint']=='ENDERECOS')){
        $form['checkpoint']='ENDERECOSIN';
        $mandaMSGWhats->enviaWhats($robot['sandboxTestNumber'],utf8_encode($msg[18]['mensagem']),$form['checkpoint']);
        $mandaMSGWhats->enviaWhats($robot['sandboxTestNumber'],utf8_encode($msg[2]['mensagem']),$form['checkpoint']);
        
    }#MENU OPÇÃO 4 - ENDERECOS OPÇÃO 4 
    elseif((strlen(trim(onlynumbers($form['message_messageText'])))==1)&&($form['message_messageText']==4)&&($r[0]['checkpoint']=='ENDERECOS')){
        $form['checkpoint']='ENDERECOSIN';
        $mandaMSGWhats->enviaWhats($robot['sandboxTestNumber'],utf8_encode($msg[18]['mensagem']),$form['checkpoint']);
        $mandaMSGWhats->enviaWhats($robot['sandboxTestNumber'],utf8_encode($msg[2]['mensagem']),$form['checkpoint']);
        
    }#MENU OPÇÃO 5 - RETIRADA OPÇÃO 1 
    elseif((strlen(trim(onlynumbers($form['message_messageText'])))==1)&&($form['message_messageText']==1)&&($r[0]['checkpoint']=='RETIRADA')){
        $form['checkpoint']='RETIRADAIN';
        $mandaMSGWhats->enviaWhats($robot['sandboxTestNumber'],utf8_encode($msg[20]['mensagem']),$form['checkpoint']);
        $mandaMSGWhats->enviaWhats($robot['sandboxTestNumber'],utf8_encode($msg[2]['mensagem']),$form['checkpoint']);
        
    }#MENU OPÇÃO 5 - ENDERECOS OPÇÃO 2 
    elseif((strlen(trim(onlynumbers($form['message_messageText'])))==1)&&($form['message_messageText']==2)&&($r[0]['checkpoint']=='RETIRADA')){
        $form['checkpoint']='RETIRADAIN';
        $mandaMSGWhats->enviaWhats($robot['sandboxTestNumber'],utf8_encode($msg[21]['mensagem']),$form['checkpoint']);
        $mandaMSGWhats->enviaWhats($robot['sandboxTestNumber'],utf8_encode($msg[2]['mensagem']),$form['checkpoint']);
        
    }#0 - SURVEI
    elseif((strlen(trim(onlynumbers($form['message_messageText'])))==1)&&($form['message_messageText']==0)&&(($r[0]['checkpoint']=='VERDOC')||($r[0]['checkpoint']=='ENDERECOS')||($r[0]['checkpoint']=='MENUOPCOES')||($r[0]['checkpoint']=='RETIRADA')||($r[0]['checkpoint']=='ENDERECOSIN')||($r[0]['checkpoint']=='OS')||($r[0]['checkpoint']=='MENUOPCOESIN'))){
        $form['checkpoint']='SURVEI';
        $mandaMSGWhats->enviaWhats($robot['sandboxTestNumber'],utf8_encode($msg[3]['mensagem']),$form['checkpoint']);
        
    }#OPÇÃO 0 - SURVEI
    elseif((strlen(trim(onlynumbers($form['message_messageText'])))==1)&&($form['message_messageText']==0)&&($r[0]['checkpoint']=='SURVEI')){
        $form['checkpoint']='FINALIZADO';
        $mandaMSGWhats->enviaWhats($robot['sandboxTestNumber'],utf8_encode($msg[4]['mensagem']),$form['checkpoint']);
                
    }#OPÇÃO 1 - SURVEI
    elseif((strlen(trim(onlynumbers($form['message_messageText'])))==1)&&($form['message_messageText']==1)&&($r[0]['checkpoint']=='SURVEI')){
        $form['checkpoint']='FINALIZADO';
        $mandaMSGWhats->enviaWhats($robot['sandboxTestNumber'],utf8_encode($msg[5]['mensagem']),$form['checkpoint']);
                
    }#OPÇÃO 2 - SURVEI
    elseif((strlen(trim(onlynumbers($form['message_messageText'])))==1)&&($form['message_messageText']==2)&&($r[0]['checkpoint']=='SURVEI')){
        $form['checkpoint']='FINALIZADO';
        $mandaMSGWhats->enviaWhats($robot['sandboxTestNumber'],utf8_encode($msg[6]['mensagem']),$form['checkpoint']);
                
    }#OPÇÃO 3 - SURVEI
    elseif((strlen(trim(onlynumbers($form['message_messageText'])))==1)&&($form['message_messageText']==3)&&($r[0]['checkpoint']=='SURVEI')){
        $form['checkpoint']='FINALIZADO';
        $mandaMSGWhats->enviaWhats($robot['sandboxTestNumber'],utf8_encode($msg[7]['mensagem']),$form['checkpoint']);
                
    }#OPÇÃO 4 - SURVEI
    elseif((strlen(trim(onlynumbers($form['message_messageText'])))==1)&&($form['message_messageText']==4)&&($r[0]['checkpoint']=='SURVEI')){
        $form['checkpoint']='FINALIZADO';
        $mandaMSGWhats->enviaWhats($robot['sandboxTestNumber'],utf8_encode($msg[8]['mensagem']),$form['checkpoint']);
                
    }#OPÇÃO 5 - SURVEI
    elseif((strlen(trim(onlynumbers($form['message_messageText'])))==1)&&($form['message_messageText']==5)&&($r[0]['checkpoint']=='SURVEI')){
        $form['checkpoint']='FINALIZADO';
        $mandaMSGWhats->enviaWhats($robot['sandboxTestNumber'],utf8_encode($msg[9]['mensagem']),$form['checkpoint']);
                
    }#Caso CPF Inválido
    else{
        $form['checkpoint'] = $r[0]['checkpoint'];#DEADLINE
        $mandaMSGWhats->enviaWhats($robot['sandboxTestNumber'],utf8_encode('Opção inválida, digite novamente.'),$form['checkpoint']);  
    }
    
    
    
}



// Grava no banco
$dados['tabela']=$robot['dataTable'];
$action->add_db($dados,$form);


