<?php
declare(strict_types=1);
header('Content-Type: text/html; charset=utf-8');
header('X-Content-Type-Options: nosniff');
header('Cache-Control: no-store');
function finish(int $code,string $message): never {http_response_code($code);echo '<!doctype html><html lang="nl"><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Aanvraag | Roetfilter Specialist</title><body style="font:17px system-ui;max-width:650px;margin:12vh auto;padding:20px"><h1>Roetfilter Specialist</h1><p>'.htmlspecialchars($message,ENT_QUOTES,'UTF-8').'</p><a href="/#aanvraag">Terug naar het formulier</a></body></html>';exit;}
if($_SERVER['REQUEST_METHOD']!=='POST') finish(405,'Gebruik het aanvraagformulier op de website.');
if(!empty($_POST['website'])) finish(200,'Bedankt voor je aanvraag.');
$origin=$_SERVER['HTTP_ORIGIN']??'';
if($origin!==''&&!in_array(parse_url($origin,PHP_URL_HOST),['roetfilter-specialist.be','www.roetfilter-specialist.be'],true))finish(403,'Ongeldige aanvraag.');
if(($_POST['akkoord']??'')!=='ja')finish(400,'Gelieve akkoord te gaan met de privacyverklaring.');
$fields=['naam'=>120,'email'=>180,'telefoon'=>40,'wagen'=>120,'bouwjaar'=>4,'kilometers'=>10,'probleem'=>120,'deeltjes'=>50,'opmerking'=>3000];
$data=[];
foreach($fields as $key=>$max){$v=trim((string)($_POST[$key]??''));if(mb_strlen($v)>$max)finish(400,'Een veld bevat te veel tekst.');$data[$key]=preg_replace('/[\x00-\x1F\x7F]/u',' ',$v);}
foreach(['naam','email','telefoon','wagen','probleem'] as $key){if($data[$key]==='')finish(400,'Vul alle verplichte velden in.');}
if(!filter_var($data['email'],FILTER_VALIDATE_EMAIL))finish(400,'Controleer je e-mailadres.');
$allowed=['Afgekeurd op de deeltjestest','Roetfilterlampje brandt','Vermogensverlies of regeneratieproblemen','Preventieve motorontkalking','Ander probleem'];
if(!in_array($data['probleem'],$allowed,true))finish(400,'Ongeldige probleemkeuze.');
$names=['inschrijving_voor'=>'Inschrijvingsbewijs voorkant','inschrijving_achter'=>'Inschrijvingsbewijs achterkant','keuring'=>'Keuringsbewijs deeltjestest','gelijkvormigheid'=>'Gelijkvormigheidsattest binnenkant'];
$mimeTypes=['image/jpeg'=>'jpg','image/png'=>'png','image/webp'=>'webp','application/pdf'=>'pdf'];
$attachments=[];$total=0;$finfo=new finfo(FILEINFO_MIME_TYPE);
foreach($names as $field=>$label){
 if(!isset($_FILES[$field])||$_FILES[$field]['error']===UPLOAD_ERR_NO_FILE)continue;
 $f=$_FILES[$field];if(is_array($f['error'])||$f['error']!==UPLOAD_ERR_OK||$f['size']>5242880||$f['size']<1||!is_uploaded_file($f['tmp_name']))finish(400,'Een bijlage is te groot of kon niet worden geüpload. Maximaal 5 MB per bestand.');
 $mime=$finfo->file($f['tmp_name']);if(!isset($mimeTypes[$mime]))finish(400,'Gebruik alleen JPG, PNG, WEBP of PDF als bijlage.');
 $total+=(int)$f['size'];if($total>15728640)finish(400,'De totale grootte van de bijlagen mag maximaal 15 MB zijn.');
 $bytes=file_get_contents($f['tmp_name']);if($bytes===false)finish(500,'Bijlage kon niet gelezen worden.');
 $attachments[]=['name'=>$field.'.'.$mimeTypes[$mime],'type'=>$mime,'data'=>$bytes];
}
$boundary='rf-'.bin2hex(random_bytes(16));$eol="\r\n";
$body="Nieuwe aanvraag via roetfilter-specialist.be".$eol.$eol;
foreach($data as $key=>$value)$body.=ucfirst(str_replace('_',' ',$key)).': '.$value.$eol;
$body.=$eol.'Bijlagen: '.count($attachments).$eol;
$message='--'.$boundary.$eol.'Content-Type: text/plain; charset=UTF-8'.$eol.'Content-Transfer-Encoding: base64'.$eol.$eol.chunk_split(base64_encode($body));
foreach($attachments as $file){$message.='--'.$boundary.$eol.'Content-Type: '.$file['type'].'; name="'.$file['name'].'"'.$eol.'Content-Disposition: attachment; filename="'.$file['name'].'"'.$eol.'Content-Transfer-Encoding: base64'.$eol.$eol.chunk_split(base64_encode($file['data']));}
$message.='--'.$boundary.'--'.$eol;
$headers=['From: Roetfilter Specialist <info@roetfilter-specialist.be>','Reply-To: '.$data['email'],'MIME-Version: 1.0','Content-Type: multipart/mixed; boundary="'.$boundary.'"'];
if(!mail('info@garagepeter.be','Nieuwe aanvraag Roetfilter Specialist',$message,implode($eol,$headers),'-finfo@roetfilter-specialist.be'))finish(503,'Verzenden lukt momenteel niet. Mail je aanvraag naar info@garagepeter.be of bel 059 33 33 11.');
finish(200,'Bedankt! Je aanvraag is verzonden. We nemen contact met je op. Dit is nog geen afspraakbevestiging.');
