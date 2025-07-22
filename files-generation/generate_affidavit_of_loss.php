<?php
require_once __DIR__ . '/../vendor/autoload.php';

// Create new PDF document
$pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

// Set document information
$pdf->SetCreator('BOSS-KIAN');
$pdf->SetAuthor('BOSS-KIAN');
$pdf->SetTitle('Affidavit of Loss');
$pdf->SetSubject('Affidavit of Loss');

// Set default header data
$pdf->SetHeaderData('', 0, '', '');
$pdf->setPrintHeader(false);
$pdf->setPrintFooter(false);

// Set margins
$pdf->SetMargins(20, 20, 20);
$pdf->SetAutoPageBreak(TRUE, 20);

// Set font
$pdf->SetFont('times', '', 12);

// Add a page
$pdf->AddPage();

// Static content (sample)
$html = <<<EOD
<div style="text-align:center; font-size:10pt;">
    <b>AMA Group of Companies<br/>AFFIDAVIT OF LOSS OF ID CARD<br/>Rev: 30 March 2002</b>
</div>yung 
<br/><br/>
<div style="text-align:left; font-size:11pt;">
    REPUBLIC OF THE PHILIPPINES<br/>
    QUEZON CITY, METRO MANILA ) S.S.<br/><br/>
    <div style="text-align:center;"><b>AFFIDAVIT OF LOSS</b></div><br/>
    I, (Empl Name), of legal age, Filipino, with residence and postal address at (Address),<br/>
    after having been duly sworn to in accordance with law hereby depose and state that:<br/><br/>
    1. I am presently employed with &lt;company&gt; &lt;branch&gt; as (position);<br/>
    2. By virtue of my employment, I was issued an Identification Card;<br/>
    3. Said Identification Card was lost sometime on __________ at ____________;<br/>
    4. Hereunder are the facts and circumstances surrounding the loss of my Identification Card:<br/>
    <br/>
    5. I made diligent search to recover the said Identification Card but proved futile;<br/>
    6. For all legal intents and purposes the said Identification Card is now considered lost and beyond recovery;<br/>
    7. I am executing this affidavit to attest to the truth of the foregoing and for whatever legal purpose/s it may serve.<br/><br/>
    <b>FURTHER AFFIANT SAYETH NAUGHT.</b><br/><br/>
    IN TRUTH THEREOF, I have hereunto set my hand this _____ day of __________ 20___ at ______________________, Philippines.<br/><br/>
    ___________________________<br/>
    Affiant<br/><br/>
    SUBSCRIBED AND SWORN to before me this _____ day of __________ 20___, affiant exhibited to me his/her Residence Cert. No. __________ issued at __________ on __________.<br/><br/>
    NOTARY PUBLIC<br/><br/>
    Doc. No. _______;<br/>
    Page No. _______;<br/>
    Book No. _______;<br/>
</div>
EOD;

$pdf->writeHTML($html, true, false, true, false, '');

// Output PDF
$pdf->Output('Affidavit_of_Loss.pdf', 'D'); 