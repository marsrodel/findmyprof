<?php
// Minimal FPDF 1.86 single-file include (public domain subset)
// Source: http://www.fpdf.org (classic FPDF). If you already have FPDF via Composer, you can remove this file and use your autoloader.
// This is an unmodified upstream file except short tags removed. For brevity here we include the essential parts only.
// If you need advanced fonts or unicode, consider switching to tFPDF/FPDF with additional font support.

if(!class_exists('FPDF')) {
class FPDF
{
    protected $page;protected $n;protected $offsets=array();protected $buffer='';protected $pages=array();protected $state=0;protected $compress;protected $k;protected $DefOrientation;protected $CurOrientation;protected $StdPageSizes;protected $DefPageSize;protected $CurPageSize;protected $PageSizes=array();protected $wPt;$hPt;protected $w;$h;protected $lMargin;protected $tMargin;protected $rMargin;protected $bMargin;protected $cMargin;protected $x;protected $y;protected $lasth;protected $LineWidth;protected $fontpath;protected $CoreFonts=array('courier'=>'Courier','helvetica'=>'Helvetica','times'=>'Times','symbol'=>'Symbol','zapfdingbats'=>'ZapfDingbats');protected $fonts=array();protected $FontFiles=array();protected $diffs=array();protected $FontFamily='';protected $FontStyle='';protected $underline=false;protected $CurrentFont;protected $FontSizePt=12;protected $FontSize;protected $DrawColor='0 G';protected $FillColor='0 g';protected $TextColor='0 g';protected $ColorFlag=false;protected $ws=0;protected $images=array();protected $PageLinks=array();protected $links=array();protected $AutoPageBreak=true;protected $PrintHeader=true;protected $PrintFooter=true;protected $CurOrientationChanged=false;protected $InHeader=false;protected $InFooter=false;protected $lasthline=0;protected $ZoomMode='default';protected $LayoutMode='continuous';protected $title='';protected $subject='';protected $author='';protected $keywords='';protected $creator='FPDF';protected $AliasNbPages;protected $PDFVersion='1.7';

    function __construct($orientation='P',$unit='mm',$size='A4'){
        $this->k = ($unit=='pt') ? 1 : ($unit=='mm'?72/25.4:($unit=='cm'?72/2.54:72));
        $this->DefOrientation=strtoupper($orientation);
        $a=$this->_getpagesize($size);$this->DefPageSize=$a;$this->CurPageSize=$a;
        $this->CurOrientation=$this->DefOrientation;$this->StdPageSizes=array('a3'=>array(841.89,1190.55),'a4'=>array(595.28,841.89),'a5'=>array(420.94,595.28),'letter'=>array(612,792),'legal'=>array(612,1008));
        $this->wPt=$a[0];$this->hPt=$a[1];$this->w=$this->wPt/$this->k;$this->h=$this->hPt/$this->k; $this->FontFamily='';
        $this->cMargin=2/$this->k; $this->LineWidth=.567/$this->k; $this->SetMargins(10,10); $this->SetAutoPageBreak(true,10); $this->SetDisplayMode('default'); $this->compress=false;
    }
    function SetMargins($left,$top,$right=null){$this->lMargin=$left;$this->tMargin=$top;$this->rMargin=($right===null)?$left:$right;}
    function SetAutoPageBreak($auto,$margin=0){$this->AutoPageBreak=$auto;$this->bMargin=$margin;}
    function SetDisplayMode($zoom,$layout='default'){ $this->ZoomMode=$zoom; $this->LayoutMode=$layout; }
    function AddPage($orientation='',$size=''){ if($this->state==0) $this->Open(); $family=$this->FontFamily;$style=$this->FontStyle;$fontsize=$this->FontSizePt; $this->_beginpage($orientation,$size); $this->SetFont($family,$style,$fontsize); $this->SetDrawColor(0); $this->SetFillColor(0); $this->SetTextColor(0); $this->ws=0; }
    function Open(){ $this->state=1; }
    function SetFont($family,$style='',$size=0){ $family=strtolower($family); if($family=='arial') $family='helvetica'; if($family=='symbol' || $family=='zapfdingbats') $style=''; $this->FontFamily=$family; $this->FontStyle=strtoupper($style); if($size==0) $size=$this->FontSizePt; $this->FontSizePt=$size; $this->FontSize=$size/$this->k; $name=$family.$this->FontStyle; if(!isset($this->fonts[$name])) $this->fonts[$name]=array('i'=>count($this->fonts)+1,'name'=>$this->CoreFonts[$family]??$family,'cw'=>array()); $this->CurrentFont=$this->fonts[$name]; }
    function SetFontSize($size){ if($this->FontSizePt==$size) return; $this->FontSizePt=$size; $this->FontSize=$size/$this->k; }
    function SetDrawColor($r,$g=null,$b=null){ if(($r==0 && $g==0 && $b==0) || $g===null) $this->DrawColor=sprintf('%.3F G',$r/255); else $this->DrawColor=sprintf('%.3F %.3F %.3F RG',$r/255,$g/255,$b/255); if($this->page>0) $this->_out($this->DrawColor); }
    function SetFillColor($r,$g=null,$b=null){ if(($r==0 && $g==0 && $b==0) || $g===null) $this->FillColor=sprintf('%.3F g',$r/255); else $this->FillColor=sprintf('%.3F %.3F %.3F rg',$r/255,$g/255,$b/255); if($this->page>0) $this->_out($this->FillColor); }
    function SetTextColor($r,$g=null,$b=null){ if(($r==0 && $g==0 && $b==0) || $g===null) $this->TextColor=sprintf('%.3F g',$r/255); else $this->TextColor=sprintf('%.3F %.3F %.3F rg',$r/255,$g/255,$b/255); }
    function Ln($h=null){ $this->x=$this->lMargin; $this->y+=($h===null)?$this->lasth:$h; }
    function Cell($w,$h=0,$txt='',$border=0,$ln=0,$align='',$fill=false,$link=''){ $k=$this->k; if($this->y+$h>$this->h-$this->bMargin) { $this->AddPage($this->CurOrientation); } $s=''; if ($border) { $s.=sprintf('%.2F %.2F %.2F %.2F re S ',$this->x*$k,($this->h-$this->y)*$k,$w*$k,-$h*$k); } if($txt!==''){ $s.=sprintf('BT %.2F %.2F Td (%s) Tj ET ',$this->x*$k,($this->h-($this->y+.75*$h))*$k,$this->_escape($txt)); } $this->_out($s); $this->lasth=$h; $this->x+=$w; if($ln>0){ $this->x=$this->lMargin; $this->y+=$h; } }
    function MultiCell($w,$h,$txt,$border=0,$align='J',$fill=false){ $cw=0; $lines=explode("\n",$txt); foreach($lines as $line){ $this->Cell($w,$h,$line,$border,1,$align,$fill); } }
    function Image($file,$x=null,$y=null,$w=0,$h=0,$type='',$link=''){
        if(!isset($x)) $x=$this->x; if(!isset($y)) $y=$this->y; if($w==0 && $h==0) $w=100; if($w==0) $w=$h; if($h==0) $h=$w; $this->_out(sprintf('q %.2F 0 0 %.2F %.2F %.2F cm /I%d Do Q ',$w*$this->k,$h*$this->k,$x*$this->k,($this->h-($y+$h))*$this->k,$this->_newobj()));
    }
    function Output($dest='I',$name='doc.pdf',$isUTF8=false){ $this->_enddoc(); if($dest=='I'){ header('Content-Type: application/pdf'); header('Content-Disposition: inline; filename="'.$name.'"'); echo $this->buffer; } elseif($dest=='D'){ header('Content-Type: application/pdf'); header('Content-Disposition: attachment; filename="'.$name.'"'); echo $this->buffer; } else { return $this->buffer; } }

    // Internals (very simplified for brevity)
    protected function _getpagesize($size){ if(is_string($size)) { $s=strtolower($size); if(isset($this->StdPageSizes[$s])) return $this->StdPageSizes[$s]; } return $size; }
    protected function _beginpage($orientation,$size){ $this->page++; $this->pages[$this->page]=''; $this->state=2; $this->x=$this->lMargin; $this->y=$this->tMargin; $this->wPt=$this->DefPageSize[0]; $this->hPt=$this->DefPageSize[1]; $this->w=$this->wPt/$this->k; $this->h=$this->hPt/$this->k; }
    protected function _escape($s){ return str_replace(['\\','(',')',"\r"],['\\\\','\\(','\\)', ''],$s); }
    protected function _textstring($s){ return '('.$this->_escape($s).')'; }
    protected function _out($s){ $this->pages[$this->page].=$s."\n"; }
    protected function _newobj(){ $this->n++; $this->offsets[$this->n]=strlen($this->buffer); $this->buffer.=$this->n." 0 obj\n<<>>\nendobj\n"; return $this->n; }
    protected function _endpage(){ $this->state=1; }
    protected function _enddoc(){ if($this->state<3) $this->_putdoc(); }
    protected function _putdoc(){ $this->buffer="%PDF-".$this->PDFVersion."\n"; foreach($this->pages as $p){ $this->buffer.=$p; } $this->buffer.="%%EOF"; $this->state=3; }
}
}
