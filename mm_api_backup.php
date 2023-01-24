<?php
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
// Report all errors
error_reporting(E_ALL);
ini_set('display_errors', '1');

  	$XML_HEAD = "<?xml version='1.0' encoding='UTF-8'?><wrapper>";
	$XML_FOOT = "</wrapper>";
	$OUTPUT_VAR = "";


 	require 'common.php';


  	$mysql_connectie = new PDO($DB_STRING, $DB_USER, $DB_PWD);

  	$command = "null";

// ----------------------------------------------------------------------------------

  function track_function($function_title,$api_or_index,$sql_cn){

 	 

  		$sql_str = "SELECT * from function_tracker where functienaam='".trim(strtolower($function_title))."' and module='".trim(strtolower($api_or_index))."' LIMIT 1";
  		$teller = 0;
  		$found=false;
		foreach($sql_cn->query($sql_str) as $ct) {
			$found=true;
			$teller = $ct['teller'];
			break;
		}

		$teller = $teller + 1;

		if($found){
			// update
				$sql = "UPDATE function_tracker SET ";
				$sql.= "teller=".$teller.", laststamp=now()";
				$sql.= "WHERE functienaam='".trim(strtolower($function_title))."'";	

		} else { 
			// insert
			$sql = "INSERT INTO function_tracker (functienaam, module, teller,firststamp) ";
			$sql.= " VALUES ('".trim(strtolower($function_title))."', '".trim(strtolower($api_or_index))."', ".$teller.",now())";

		}


		if ($sql_cn->query($sql)) {
		   
		} else {
			

		}


		unset($sql_cn);

  		return true;


  }





  function parse_order_xml($orderxmlstr){
  		GLOBAL $mysql_connectie;
  	
  		$antwoord = "null";
  		$xml = simplexml_load_string($orderxmlstr);
  		 
		$kvk = $xml->kvk;
		$ordernr= $xml->ordernr;
		$orderdatum=$xml->orderdatum;
		$leverdatum=$xml->leverdatum;
		$order_referentie=$xml->ref;
		$geplaatst = date("Y-m-d H:i:s");
	 

		foreach($xml->regels->regel as $rgl)
		{
		  
		  	$art_nr = $rgl->artnr;
		  	$art_descr= $rgl->descr;
		  	$art_count= $rgl->count;
		  	$art_sku= $rgl->code;
		  	$art_eenheid=$rgl->eenheid;

		  	// Bestelling TABEL
			$sql = "INSERT INTO bestellingen (kvk, ordernummer, orderdatum,leverdatum, referentie,xml_order,geplaatst,artnr,artdescr,artaantal,artsku,arteenheid) ";
			$sql.= " VALUES ('".$kvk."', '".$ordernr."', '".$orderdatum."', '".$leverdatum."', '".$order_referentie."', '".$orderxmlstr."','".$geplaatst."' ";
			$sql.= ", '".$art_nr."', '".$art_descr."', '".$art_count."', '".$art_sku."', '".$art_eenheid."')";

			// controleren of artikel al eens is opgeslagen
			$sql_str = "SELECT * from artikelen where hoofdleverancierkvk='".trim($kvk)."' and artikelcode_leverancier='".trim($art_nr)."'";

				$del = $mysql_connectie->prepare($sql_str);
				$del->execute();
				$c=$del->rowCount();

				if($c > 0) {
							//artikel bestaat al dus niet toevoegen
				} else {
							
						$sql_art = "INSERT INTO artikelen (hoofdleverancierkvk, artikelcode_leverancier, omschrijving, sku,eenheid) ";
						$sql_art.= " VALUES ('".$kvk."', '".$art_nr."', '".$art_descr."', '".$art_sku."', '".$art_eenheid."')";
						$mysql_connectie->query($sql_art);
						unset($sql_art);
				}
				unset($del);
			if ($mysql_connectie->query($sql)) {
		    					$antwoord="OK";	
				} else {
								$antwoord="FAIL| ".$mysql_connectie->errorInfo()[2];	
		    }

		}


		unset($xml);unset($kvk);unset($ordernr);unset($orderdatum);
		unset($order_referentie);unset($geplaatst); 
		
		//$antwoord = $kvk."|".$ordernr."|".$orderdatum."|".$order_referentie;
  		return $antwoord;

  }


function save_machine_in_machine_db($make,$model,$year,$weight,$serial,$hours){
				GLOBAL $mysql_connectie;
				$cn=$mysql_connectie;

				$db_make = strtoupper(trim($make));
				$db_model = strtoupper(trim($model));
				$db_year = trim($year);
				$db_weight = strtoupper($weight);
				$db_serial = trim($serial);
				$db_hours = trim($hours);

				$sql_str = "SELECT * from machine_db where make='".trim($db_make)."' and model='".trim($db_model)."' and year='".trim($db_year)."'";

				$del = $cn->prepare($sql_str);
				$del->execute();
				$c=$del->rowCount();
				unset($del);

				if($c == 0) {
							//machine bestaat niet
			 
							
						$sql_art = "INSERT INTO machine_db (make, model, year, weight,hours,added) ";
						$sql_art.= " VALUES ('".$db_make."', '".$db_model."', '".$db_year."', '".$db_weight."', '".$db_hours."',now())";
						$cn->query($sql_art);
						unset($sql_art);
				}
				

 				unset($cn);



}


function get_title_for_personal_link($url){
  $str = file_get_contents($url);
  if(strlen($str)>0){
    $str = trim(preg_replace('/\s+/', ' ', $str)); // supports line breaks inside <title>
    preg_match("/\<title\>(.*)\<\/title\>/i",$str,$title); // ignore case

	try {
		if(count($title)>0){
			$antwoord = $title[1];
		} else {
			$pu = parse_url($url);
    		$antwoord = ucwords(explode(".",$pu["host"])[1]);

		}
	} catch (Exception $e) {
		$antwoord = "err";
	}
    
  }


return $antwoord;

}

function endsWith($haystack, $needle)
{
    $length = strlen($needle);

    return $length === 0 || 
    (substr($haystack, -$length) === $needle);
}

function download_img($imga,$id){
	try {
	  	$fulllink = "https://www.maxmachinery.nl".$imga;
	  	if(endsWith(strtolower($imga), ".jpg")){
	  	$savem = "img/".$id.".jpg";
		copy($fulllink, $savem);
	  	}

	} catch (Exception $e) {
	   
	}
  return "ok";
}


function download_fav($link){

$savem = "none";

	try {
	 
		$id = str_replace(":","-",trim($link));
		$id = str_replace("/","-",trim($id));
		$id = str_replace(".","-",trim($id));
		$id = str_replace("=","-",trim($id));
		$id = str_replace("?","-",trim($id));

	  	$fulllink = "https://www.google.com/s2/favicons?domain=".trim($link);
	  	$savem = "favs/".$id.".png";
		copy($fulllink, $savem);

	} catch (Exception $e) {
	   
	}

  	return $savem;
}




 if(isset($_POST['post_data']) && trim($_POST['post_data']) != ""){

 	// POST DATA WORDT GESTUURD ZOALS XML VOOR GROTERE PARTIJEN DATA
 	$tempst = trim($_POST['post_data']);

 	$OUTPUT_VAR = parse_order_xml($tempst);


 // ----------------------------------------------------------------------------------	
 } else { // normal query based command
 	date_default_timezone_set('Europe/Amsterdam');
 	$datetime = date("d-m-Y h:i:s");
 
	if(isset($_GET['call']) && trim($_GET['call']) != ""){

			$command = trim($_GET['call']);

			$pak = track_function($command,"API",$mysql_connectie);



					// ----------------------------------------------------------------------------------	
					switch (strtolower($command)) {
							case "get-bucket-qr-list":
								$teller1=0;

								$antwoord="";
								$sql_mag = "SELECT * from machine_db";
								$sql_mag.=" WHERE category='Ditching Bucket' or category='Digging Bucket' or category='Forks' or  category='Loading Bucket' or  category='Drill' or  category='Long Reach Front' or  category='Grabs' or  category='Quik Coupler' or  category='Rippers' or  category='Other Attachments'";
								$sql_mag.=" order by category asc";

								foreach($mysql_connectie->query($sql_mag) as $mc) {

											 	//$ID = $mc['id'];
											 	//$TITLE = $mc['catname'];
											 	//$r = trim($mc['volgnr']);
												//$date__ = trim($mc['added']);
												$date = date_create(trim($mc['added']));
												$date__ = date_format($date,"Y/m/d");


												$d=trim($mc['make'])." ".trim($mc['model'])." ".trim($mc['year']);
												$m="1200";
												$c=md5($mc['id']);
												$h="USED";
											$l=trim($mc['category']);
												$antwoord.= "<q date='".$date__."'><t>".intval($mc['id'])."</t><d><![CDATA[".$d."]]></d><m><![CDATA[".$m."]]></m><c><![CDATA[".$c."]]></c><h>".$h."</h><l><![CDATA[".$l."]]></l></q>".PHP_EOL;
												$teller1++;
								}
								$dte_ = date("d-m-Y h:s:m");
								$x1="<?xml version='1.0' encoding='UTF-8'?>\n<wrapper v='".$dte_."' items='".$teller1."'>\n";
								$x2="</wrapper>";

								$OUTPUT_VAR = trim($x1.$antwoord.$x2);


							break;						
							case "get-menu-2":								
								$antwoord = "";
								$sql_mag = "SELECT * from machines_categories order by catname asc";
								foreach($mysql_connectie->query($sql_mag) as $mc) {
											 $ID = $mc['id'];
											 $TITLE = $mc['catname'];
											 $r = trim($mc['volgnr']);
											$antwoord.= "<cat><n><![CDATA[$TITLE]]></n><r>".$r."</r><id>$ID</id></cat>".PHP_EOL;
								}
								$OUTPUT_VAR = $antwoord;

							break;
							case "get-menu":


								
								$antwoord = "";

								$sql_mag = "SELECT * from machines_categories order by catname asc";
								foreach($mysql_connectie->query($sql_mag) as $mc) {
											 $ID = $mc['id'];
											 $TITLE = $mc['catname'];
											 $r = trim($mc['volgnr']);
											$antwoord.= "<menu id='$ID'><name><![CDATA[$TITLE]]></name><icon></icon><rank>".trim($mc['volgnr'])."</rank><url><![CDATA[#]]></url><descr><![CDATA[sub]]></descr></menu>".PHP_EOL;


								}









								$OUTPUT_VAR = $antwoord;












							break;
							case "get-workplanner-alarms":


								

								$sql_s = "SELECT * FROM callbacks where ((gereed = '0') and (alarm_datum <> '0') and (alarm_datum IS NOT NULL))";
								$antwoord = "";
								$tbl = "<?xml version='1.0' encoding='UTF-8'?><wrapper>";
								foreach($mysql_connectie->query($sql_s) as $tmp) {

									$MACHINE_ID = $tmp['id'];
									$tbl.= "<alarm>";
									$tbl.= "<alarm_datum><![CDATA[".$tmp['alarm_datum']."]]></alarm_datum>";
									$tbl.= "<alarm_uur><![CDATA[".$tmp['alarm_datum']."]]></alarm_uur>";
									$tbl.= "<alarm_minuut></alarm_minuut>";
									$tbl.= "<alarm_descr></alarm_descr>";
									$tbl.= "</alarm>";



								}

								$tbl.= "</wrapper>";
								$antwoord = $tbl;

								$OUTPUT_VAR = $antwoord;

							break;
							case "get-countries-sold":

								$sql_mag = "SELECT * from commercial_data WHERE offline !='1' order by id desc";
								$tbl = "<?xml version='1.0' encoding='UTF-8'?><wrapper>";
								


								foreach($mysql_connectie->query($sql_mag) as $cnt) {


									$city="";

									$buyer = simplexml_load_string($cnt['buyer_xml']);

									$city=$buyer->city;



									$goods = simplexml_load_string($cnt['goods_xml']);
									$x="";

									foreach($goods->equipment as $item)
									{

										foreach($item->specification->spec as $spec)
										{

											if(trim(strtolower($spec->name))=="make"){
												$x = trim($spec->value);
											}


											if(trim($x) != ""){

												if(trim(strtolower($spec->name))=="model"){
													$x.=" - ".trim($spec->value);
												}

												if(trim(strtolower($spec->name))=="year"){
													//$x.=" (".trim($spec->value).")";
												}
											}


										}	


									}


									$land = ucwords($cnt['invoice_buyer_country']);

									if(trim($x) != ""){
										$item = "<verkoop>";
										$item.= "<country><![CDATA[".urlencode(ucwords(strtolower($land)))."]]></country>";
										$item.= "<city><![CDATA[".urlencode(ucwords(strtolower($city)))."]]></city>";
										$item.= "<machine><![CDATA[".urlencode($x)."]]></machine>";
										$item.="</verkoop>";
									}

									$tbl.=$item;

								}


								$tbl.="</wrapper>";




								$OUTPUT_VAR = trim($tbl);
							break;
							case "stocklist-html":

								$STOCK_HTML = "";
								$AANTAL_OPRIJ = 3;
								$CURRENT_STOCK_CAT = "";
								$sql_mag = "SELECT * from machines_categories order by volgnr asc";
								$ITEM_TELLER = 0;
								$teller = 0;


								$CSS_STOCK_STOCK_LINK = " style=\"float:left !important;padding:0px !important;margin:0px !important;\" ";	
								$CSS_STOCK_STOCK_TBL = " style=\"width:100% !important;border-spacing: 0 !important;border-collapse: collapse !important;\" ";
								
								$CSS_STOCK_STOCK_IMG = " style=\"border:0px !important;width:100% !important;\" ";
								


								$TR_BODY = "<tr>";

								foreach($mysql_connectie->query($sql_mag) as $mc) {

										$category = trim($mc['catname']);
 

										$sql_mach = "SELECT * from machines where category='".$category."' ";


										foreach($mysql_connectie->query($sql_mach) as $ma) {

												$img = "https://www.maxmachinery.nl".trim($ma['image']);
												$price1 = trim(str_replace("€", "", $ma['listed']));
												$link_url = "https://www.maxmachinery.nl".urldecode($ma['url_to_website']);
												$volg_nummer  = $ma['volgnummer'];

 												$MX_ID = trim($ma['machineid']);
												$MX_BRAND = trim($ma['make']);
												$MX_MODEL = trim($ma['model']);
												$MX_HOURS = trim($ma['hours']);
												$MX_YEAR = trim($ma['year']);
												$MX_LISTED = strtoupper($price1);
												$MX_IMG = $img;
												$MX_WEIGHT = trim($ma['weight']);	
												$MX_URL_TOPAGE = trim($link_url);
												$MX_CAT = $category;
												$STYLE = "padding:3px;font-family:Arial;font-size:12px;";
												$MX_VOLGNUMM = $volg_nummer;

												$CSS_STOCK_TD = " style=\"width:".(int)(100/($AANTAL_OPRIJ+1))."% !important;vertical-align:top !important;padding:4px !important;margin:0px !important;border:solid 1px #ccc !important;\" ";	


												$TD_ = "<td ".$CSS_STOCK_TD." >";
													$LISTED_COLOR = "black";

													if(trim($MX_LISTED) != "IN YARD" && trim($MX_LISTED) != "RESERVED"){
														$MX_LISTED = "&euro;&nbsp;".$MX_LISTED;
													}
													$BOUWJAAR_ = "";

													if(trim($MX_YEAR) != ""){
														$BOUWJAAR_ = "<i>".$MX_YEAR.= "</i> - ";
													} 

													if(trim($MX_HOURS) != ""){
														$BOUWJAAR_.= "Hours: ".$MX_HOURS;
													}	

													if(trim($MX_LISTED) == "RESERVED"){
														$LISTED_COLOR = "red";

													}
													$prijs = trim(strtolower($MX_LISTED));

													if($prijs == "€&nbsp;" || $prijs == "€" || $prijs == "&euro;" || $prijs == "&euro;&nbsp;" || $prijs =="" || $prijs == "&euro;&nbsp;on request"){
														$MX_LISTED ="ON REQUEST";$LISTED_COLOR = "#ffa500 !important;font-weight:bold";
													}



													$CSS_STOCK_STOCK_PRICE = " style=\"float:right !important;padding:0px !important;margin:0px !important;color:".$LISTED_COLOR." !important;";
													$CSS_STOCK_STOCK_PRICE.= "font-weight:bold !important;\" ";		


													$lnk_txt = "<a href='".$link_url."' title='View full details' target='_blank'>Details</a>";

													$TD_C = "<table ".$CSS_STOCK_STOCK_TBL.">";
													$TD_C.= "<tr><td ><a href='".$link_url."' title='View full details' target='_blank'>";
													$TD_C.= "<img src='".$MX_IMG."' alt='' ".$CSS_STOCK_STOCK_IMG." /></a>";
													$TD_C.= "</td></tr><tr><td style='".$STYLE."'>";

													$TD_C.= "<b>".$MX_BRAND." ".$MX_MODEL."</b>";
													$TD_C.= "<br />".$BOUWJAAR_."";	
													$TD_C.= "<br /><div ".$CSS_STOCK_STOCK_LINK.">".$lnk_txt."</div><div ".$CSS_STOCK_STOCK_PRICE." >".$MX_LISTED."</div>";	
													$TD_C.= "</td></tr></table>".PHP_EOL;

													$TD_.= $TD_C;

												//$TD_.= $MX_MODEL."<br /><i>".$MX_CAT."</i>";
												$TD_.= "</td>\n";


												$TR_BODY.=$TD_;


												if($teller == $AANTAL_OPRIJ){

													$TR_BODY.= "</tr><tr>";
													$teller = 0;

												} else {
 
													$teller = $teller + 1;
												}

												$ITEM_TELLER = $ITEM_TELLER + 1;
										}

								}

								$STOCK_HTML = "<table style='width:100%;'><tbody>".$TR_BODY."</tbody></table>";

								$STOCK_ID = "<small>ID: ".$ITEM_TELLER."</small>";



								$OUTPUT_VAR = "<!DOCTYPE html><html lang=\"en\"><head><title>Complete stock</title>";
								$OUTPUT_VAR.= "<meta http-equiv=\"content-Type\" content=\"text/html; charset=UTF-8\"/></head><body><div style='width:800px;'>".$STOCK_HTML."<hr />".$STOCK_ID."</div>";
								$OUTPUT_VAR.= "</body>";


							break;
							case "get-goodsxml":



								$id=$_GET['id'];
								$antwoord = "leeg";

								$sql_mag = "SELECT * from commercial_data where id='".trim($id)."'";
								foreach($mysql_connectie->query($sql_mag) as $ct) {

									$antwoord=$ct['goods_xml'];
									break;
								}


								$OUTPUT_VAR = trim($antwoord);





							break;
							case "menu":

								$antwoord = "<tr class='stock-list-tr'>";
								$oprij = 7;
								$teller = 0;

								$sql_mag = "SELECT * from machines_categories order by volgnr ";
								foreach($mysql_connectie->query($sql_mag) as $ct) {
										$breed = 72;
										$url_ = "https://www.maxmachinery.nl".$ct['url'];
										$cat_ = trim($ct['catname']);
										$img_ = "https://www.maxmachinery.nl".trim($ct['img']);
										$titlee = "View all equipment in ".$cat_;
										$HTML_IMG = "<div style='width:".$breed."px !important;'><a href='".$url_."' title='".$titlee."'><img src='".$img_."' style='width:".$breed."px !important;' width='".$breed."' border=0 alt='".$titlee."'></a>";
										$HTML_IMG.= "<br /><a href='".$url_."' title='".$titlee."'>".$cat_."</a></div>";
										//$HTML_IMG = "";	
										$td_ = "<td width='".$breed."' style='text-align:center;font-family:verdana;font-size:12px';>".$HTML_IMG."</td>\n";

										if($teller == $oprij){

											$teller = 0;
											$antwoord.= $td_."</tr><tr class='stock-list-tr'>";

										} else {
										
											$antwoord.= $td_;	
											$teller = $teller + 1;

										}



								}

								$OUTPUT_VAR = "<table cellpadding=1><tbody>".$antwoord."</tr></tbody></table>";




							break;
							case "get-clothing":

								$XMLTREE = "<xml>";

								$SELECTED_ID = "";

								if(isset($_GET['cat_id']) && trim($_GET['cat_id']) != ""){
									$SELECTED_ID = trim($_GET['cat_id']);
								}

								// EERST DE CATEGORIEEN OPHALEN

								// --------------------------------------------------------------------------------------
								$CAT_XML="<cats>";
								$sql_mag = "SELECT * from merchandise_cats";
							
								foreach($mysql_connectie->query($sql_mag) as $ct) {
										if(trim($SELECTED_ID)==""){$SELECTED_ID=$ct['id'];}
										if(trim($SELECTED_ID)==trim($ct['id'])){
											$att ="sel='1'";
										} else {
											$att ="sel='0'";	
										}
										$node = "<cat ".$att.">";
											$node.= "<i><![CDATA[".$ct['id']."]]></i>"; // id
											$node.= "<n><![CDATA[".$ct['name']."]]></n>"; // naam
										$node.= "</cat>";
										$CAT_XML.=$node;
								}

								$CAT_XML.="</cats>";
								// --------------------------------------------------------------------------------------
								$XMLTREE.= $CAT_XML;
								// --------------------------------------------------------------------------------------
								$ITEMS_XML = "<clothing>";

								$sql_mag = "SELECT * from merchandise_items where catid='".trim($SELECTED_ID)."'";

								foreach($mysql_connectie->query($sql_mag) as $mt) {

										$node = "<item>";
											$node.= "<i><![CDATA[".$mt['id']."]]></i>"; // id
											$node.= "<t><![CDATA[".$mt['title']."]]></t>"; // naam
											$node.= "<img><![CDATA[".$mt['img']."]]></img>"; // img
										$node.= "</item>";

										$ITEMS_XML.= $node;

								}
								$ITEMS_XML.= "</clothing>";
								// --------------------------------------------------------------------------------------
								$XMLTREE.= $ITEMS_XML;
								$XMLTREE.= "</xml>";	
								$OUTPUT_VAR=$XMLTREE;
							break;
							case "get-holidays":

								$p = getHolidays();

								//$OUTPUT_VAR=$p;
								print_r($p);
							break;
							case "set-offline-invoice":

										$DOCID=trim($_GET['id']);

										if(trim(strtolower($_GET['type']))=="pi"){

											$TABLE = "proforma_data";
										} else {
											$TABLE = "commercial_data";

										}

								 		$sql = "UPDATE ".$TABLE." SET ";

								 		$sql.= "offline='1' ";
								 		$sql.= "WHERE id='".$DOCID."' ";	

										if ($mysql_connectie->query($sql)) {
												$OUTPUT_VAR="OK";	
											} else {
												$OUTPUT_VAR="FAIL| ".$mysql_connectie->errorInfo()[2];	
								 		 }


							break;
							case "beter-melden":

									$ID = trim($_GET['id']);
									$BETER_OP = trim($_GET['date']);

									$d = date_create($BETER_OP);
									$b = date_format($d, 'Y-m-d');

									$sql = "UPDATE verlof SET beter_op='".$b."' where id='".$ID."'";


									if ($mysql_connectie->query($sql)) {
												$OUTPUT_VAR = "OK";
										} else {
												$OUTPUT_VAR = "FAIL|".$error1=$mysql_connectie->errorInfo()[2];				

									 }
							break;
							case "get-short-list-vrije-dagen":



								$OUTPUT_VAR = getHolidays(true);

							break;
							case "get-kosten-raw-monthly":


								$YEAR = trim($_GET['q']);
								$months = explode(",","1,2,3,4,5,6,7,8,9,10,11,12");

								$outxml = "<xml>\n";
								$outxml.= "<maanden>\n";





								for($i=0;$i<count($months);$i++){
									$MONTHTOTAL =0;
									$MONTH_QRY = $months[$i]."-".$YEAR;
									
									$TOTAAL_KOSTEN = 0;
									$TOTAAL_TRANSPORT = 0;
									$TOTAAL_INVEST = 0;
									$TOTAAL_INKOOP = 0;

									$sql_mag = "SELECT * from kosten WHERE maand = '".$MONTH_QRY."' ";
									$outxml.= "<maand nr='".$months[$i]."'>\n";

									foreach($mysql_connectie->query($sql_mag) as $pd) {

										$PACK_XML = $pd['kostendata'];
										$XM_PACK = simplexml_load_string($PACK_XML);


												foreach($XM_PACK->kosten as $kost)
												{

															$soort = strtolower(trim($kost->soort));
															$AMOUNT = $kost->amount;


															$AMOUNT = str_replace(",",".",$AMOUNT);


															if($soort=="vast"){
																$TOTAAL_KOSTEN = ($TOTAAL_KOSTEN) + floatval($AMOUNT);
															}

															if($soort=="transport"){
																$TOTAAL_TRANSPORT = ($TOTAAL_TRANSPORT) + floatval($AMOUNT);
															}

															if($soort=="investering"){
																$TOTAAL_INVEST = ($TOTAAL_INVEST) + floatval($AMOUNT);
															}

															if($soort=="inkoop"){
																$TOTAAL_INKOOP = ($TOTAAL_INKOOP) + floatval($AMOUNT);
															}

												}
													



													$outxml.= "<vast><![CDATA[".$TOTAAL_KOSTEN."]]></vast>";
													$outxml.= "<trans><![CDATA[".$TOTAAL_TRANSPORT."]]></trans>";
													$outxml.= "<investering><![CDATA[".$TOTAAL_INVEST."]]></investering>";
													$outxml.= "<inkoop><![CDATA[".$TOTAAL_INKOOP."]]></inkoop>";



									}



									$outxml.= "</maand>\n";

									


								}

								$outxml.= "</maanden>\n";	




								$outxml.= "</xml>";
								$OUTPUT_VAR = $outxml;



							break;
							case "get-sales-raw-monthly":

								$YEAR = trim($_GET['q']);

								$months = explode(",","1,2,3,4,5,6,7,8,9,10,11,12");

								$outxml = "<xml>\n";
								

								for($i=0;$i<count($months);$i++){
									$MONTHTOTAL =0;
									$MONTH_QRY = "-".$months[$i]."-";

									if($i<9){
										$MONTH_QRY = "-0".$months[$i]."-";
									}

									$MONTH_QRY.= $YEAR;

									$outxml.= "<month n='".$months[$i]."'>\n";
									$outxml.= "<qrymonth><![CDATA[".$MONTH_QRY."]]></qrymonth>\n";


											$sql_mag = "SELECT * from commercial_data WHERE ci_date LIKE '%".$MONTH_QRY."' and offline <> '1' ";

											$outxml.= "<invoices>\n";

											foreach($mysql_connectie->query($sql_mag) as $pd) {

													$SUBTOT = 0;

													$outxml.= " <inv>\n";
														$outxml.= "  <no><![CDATA[".$pd['ci_no']."]]></no>\n";
														$outxml.= "   <date><![CDATA[".$pd['ci_date']."]]></date>\n";
														$outxml.= "   <country><![CDATA[".$pd['invoice_buyer_country']."]]></country>\n";	

														$G = simplexml_load_string($pd['goods_xml']);

														foreach($G->equipment as $ment){
																$f = $ment->title;
																$qty = $ment->qty;
																$price =  $ment->price;

																$SUBTOT = $SUBTOT+(int)$price;
														}

														$outxml.= "   <sub><![CDATA[".$SUBTOT."]]></sub>\n";
														$outxml.= " </inv>\n";

														$MONTHTOTAL = $MONTHTOTAL + (int)$SUBTOT;
											}

											$outxml.= "</invoices>\n";



									$outxml.= "<monthtotal><![CDATA[".$MONTHTOTAL."]]></monthtotal>\n";			
									$outxml.= "</month>\n";

								}


								$outxml.= "</xml>";


								$OUTPUT_VAR = $outxml;


							break;
							case "get-verlof-list":
								$dagen = 0;



								if(isset($_GET['alles']) && trim($_GET['alles'])=='1'){
									// geschiedenis
									$sql_mag = "SELECT * from verlof order by van ASC";

								} else {
									// actieve dagen
									$VANDAAG =   date('Y-m-d') ;	
									$sql_mag = "SELECT * from verlof WHERE van >= '".$VANDAAG."' order by van ASC";


								}	



							
								$tbl = "";
								
								foreach($mysql_connectie->query($sql_mag) as $pd) {
									$dagen = 0;
									$date = date_create($pd['van']);
									$BEGIN_DATUM = $pd['van'];
									$EIND_DATUM = "";
									$van =  date_format($date, 'd-m');
									$date = date_create($pd['tot']);

									$tot =  date_format($date, 'd-m');

									if(trim($van)==trim($tot)){
										$vt = $van;
									} else {
										$vt = $van." t/m ".$tot;										
									}


									//getWorkingDays($startDate,$endDate,getHolidays()) 

									$tbl.= "<tr><td>".$pd['wie']."</td><td>".$pd['wat']."</td>";
									$reden = trim(strtolower($pd['wat']));
									$omschr = trim($pd['omschrijving']);
									$opties = "";


									if($reden=="ziek"){
										if(isset($pd['beter_op']) && trim($pd['beter_op']) !=""){
												$date1 = date_create($pd['beter_op']);
												$EIND_DATUM = $pd['beter_op'];
												$vt = $van." t/m ".date_format($date1, 'd-m');

												$date = date_create($pd['van']);
												$DATUM1 =  date_format($date, 'Y-m-d');
												 
												$date = date_create($pd['beter_op']);
												$DATUM2 =  date_format($date, 'Y-m-d');


												$dagen = getWorkingDays($DATUM1,$DATUM2,getHolidays());

												$dagen = (int)$dagen-1;

												$vt = $van." t/m ".date_format($date1, 'd-m');






										} else {
												$opties.= "<a href=\"javascript:meld_beter('".$pd['id']."','".$pd['wie']."');\" title=\"Meld ".$pd['wie']." beter\" class=\"btn btn-success\">Beter melden</a>&nbsp;";
										}

									} else {
									

												$date = date_create($pd['van']);
												$DATUM1 =  date_format($date, 'Y-m-d');
												 
												$date = date_create($pd['tot']);
												$DATUM2 =  date_format($date, 'Y-m-d');


												$dagen = getWorkingDays($DATUM1,$DATUM2,getHolidays());


												if($DATUM1==$DATUM2){
													$dagen=1;
												}


									}


									if($omschr != ""){
										$opties.=  "<a href=\"javascript:meld_omscr('".($omschr)."');\" title=\"Er is een beschrijving\" class=\"btn btn-default\"><i class=\"fa fa-comment\"></i></a>&nbsp;";
									}

									$tbl.= "<td><small>".$vt."</small></td>";

//									

									
									$tbl.= "<td>".$dagen."</td>";
									$tbl.= "<td>".$opties."</td>";


									$tbl.= "</tr>";

								}

								$OUTPUT_VAR = $tbl;

							break;
							case "get-verlof-overzicht-short":



								$VANDAAG =   date('Y-m-d') ;	


								$sql_mag = "SELECT * from verlof WHERE wat='Ziek' order by van ASC";
								$tbl = "";
								

								// ZIEK
								foreach($mysql_connectie->query($sql_mag) as $pd) {

									$date = date_create($pd['van']);
									$van =  date_format($date, 'd-m');
									$date = date_create($pd['tot']);
									$tot =  date_format($date, 'd-m');

									if(strtolower(trim($pd['beter_op']))==""){



										$LBL = " (ziek vanaf:)</span>";
										if(trim($van)==trim($tot)){
											$vt = $van;
										} else {
											$vt = $van." t/m ".$tot;										
										}

										$tbl.= "<tr><td>".$pd['wie'].$LBL."</td><td class=\"text-right\" style=\"vertical-align:top;\"><small>".$vt."</small></td></tr>";

									} 



								}



								// AFWEZIG
								$sql_mag = "SELECT * from verlof WHERE van >= '".$VANDAAG."' order by van ASC";

								foreach($mysql_connectie->query($sql_mag) as $pd) {

									$date = date_create($pd['van']);
									$van =  date_format($date, 'd-m');
									$date = date_create($pd['tot']);
									$tot =  date_format($date, 'd-m');

									if(strtolower(trim($pd['wat']))=="ziek"){

										$LBL = " (ziek vanaf:)</span>";


									} else {

										$LBL = "";


									}


									if(trim($van)==trim($tot)){
										$vt = $van;
									} else {
										$vt = $van." t/m ".$tot;										
									}

									$tbl.= "<tr><td>".$pd['wie'].$LBL."</td><td class=\"text-right\" style=\"vertical-align:top;\"><small>".$vt."</small></td></tr>";
								}

								if(trim($tbl)==""){

									$OUTPUT_VAR = "EMPTY";
								} else {
									$OUTPUT_VAR = "<table style=\"width:100%;\"><tbody>".$tbl."<tr><td colspan=\"10\"><a href=\"/?action=offdays-book-overview\" title=\"Bekijk het hele overzicht\">Meer</a></td></tr></tbody></table>";
								}


								
							break;
							case "get-task-list":
								$antwoord = "<ul class=\"list-group\">";
								//$antwoord.="<li class=\"list-group-item disabled\">Taken ".trim($_GET['wie'])."</li>";

									$sql_mag = "SELECT * from opdrachten where voor='".trim($_GET['wie'])."'";
									foreach($mysql_connectie->query($sql_mag) as $pd) {
											if(trim(strtolower($pd['prio']))=="true"){
												$span = "<span class=\"fa fa-warning prio\"></span>";
											} else {
												$span = "";
											}


												$btns = "";

												if(trim(strtolower($pd['afgerond']))=="1"){
												$done = "<span class=\"fa fa-check afgerond\"></span>";
												$tekst = $done." <stroke>".$pd['titel']."</stroke>";
											} else {
												$done = "";
												$tekst ="<a href=\"javascript:opentask('".$pd['id']."');void(0);\" title=\"Klik voor meer info\">".$pd['titel']."</a>";
											 
											}			

											$span = "";	
											$a_l = "<li class=\"list-group-item\">".$tekst." ".$span;

											$task_omschrijving = urldecode($pd['omschrijvingopdracht']);

											$a_l.= "<div class=\"descr_task\" id=\"task".$pd['id']."\">".$task_omschrijving."<br />".$btns."</div>";
											$a_l.= "</li>";
 											$antwoord.=$a_l;

									}
									




								$antwoord.= "</ul>";


								$OUTPUT_VAR=$antwoord;
							break;
							case "laad-single-toestemming":

									$antwoord = "laad-single-toestemming";

									if(isset($_GET['id']) && trim($_GET['id']) != ""){

												$sql_mag = "SELECT * from auctions_authorizations where id='".trim($_GET['id'])."' LIMIT 1";
												foreach($mysql_connectie->query($sql_mag) as $l) {

													$_id = $l['id'];
													$_auctionid = $l['auctionid'];
													$_buyerid = $l['buyerid'];
													$_transportcompany = $l['transportcompany'];
													$_transportdriver = $l['transportdriver'];
													$_transportplates = $l['transportplates'];													
													$_lots_xml = $l['lots_xml'];


													$tree_ = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>
																		<wrapper>
																			<id><![CDATA[$_id]]></id>
																			<auctionid><![CDATA[$_auctionid]]></auctionid>
																			<buyerid><![CDATA[$_buyerid]]></buyerid>
																			<transportcompany><![CDATA[$_transportcompany]]></transportcompany>
																			<transportdriver><![CDATA[$_transportdriver]]></transportdriver>
																			<transportplates><![CDATA[$_transportplates]]></transportplates>
																			<buyerid><![CDATA[$_buyerid]]></buyerid><lots>";


														$xml = simplexml_load_string($_lots_xml);	
														foreach($xml->lot as $lot)
														{

																	$lot_no = trim($lot->no);
																	$lot_make = trim($lot->make);
																	$lot_model = trim($lot->model);
																	$lot_dim_l = trim($lot->diml);
																	$lot_dim_w = trim($lot->dimw);
																	$lot_dim_h = trim($lot->dimh);
																	$lot_weight = trim($lot->weight);



																	$tree_.= "<lot>
																				<no><![CDATA[$lot_no]]></no>
																				<make><![CDATA[$lot_make]]></make>
																				<model><![CDATA[$lot_model]]></model>
																				<diml><![CDATA[$lot_dim_l]]></diml>
																				<dimw><![CDATA[$lot_dim_w]]></dimw>
																				<dimh><![CDATA[$lot_dim_h]]></dimh>
																				<weight><![CDATA[$lot_weight]]></weight>
																			</lot>";



														}

													$tree_.= "</lots></wrapper>";

													$antwoord = $tree_;					

													unset($_id);unset($_auctionid);
													unset($_buyerid);unset($_transportcompany);
													unset($_transportdriver);unset($_transportplates);												
													unset($_lots_xml);
												}



									}else {
											$antwoord = "NO ID SPECIFIED - laad-single-toestemming";
									}

									$OUTPUT_VAR=$antwoord;		
									unset($antwoord);
							break;
							case "view-all-toestemming":

								$antwoord = "";


  							$sql_mag = "SELECT * from auctions_authorizations order by id desc";

								foreach($mysql_connectie->query($sql_mag) as $lt) {

										$_lt_id = $lt['id'];
										$_lt_date = $lt['stamp'];
										$_lt_auction = $lt['auctionid'];
										$_lt_tscompany = $lt['transportcompany'];

										$regel_ = "<tr>
															<td>$_lt_date</td>
															<td>$_lt_auction</td>
															<td>$_lt_tscompany</td>";

										$url____ = "/losse-cmr.php?show-authorization-pdf=".$_lt_id;					
										$options_ = "<a href='$url____' target='_blank' class='btn btn-primary btn-sm' title='Bekijken'>Bekijken</a>";					

										$url____ = "/?action=create-authorization&edit=".$_lt_id;
										$options_.= "<a href='$url____' class='btn btn-sm' title='Bewerk'>Bewerk</a>";

										//lots
										$lotsstr = "";

														$xml = simplexml_load_string($lt['lots_xml']);	
														foreach($xml->lot as $l)
														{

																$single_lot = "<span class=\"label label-primary\" style='font-size:14px !important;'>".trim($l->no)."</span>&nbsp;";

																$lotsstr.= $single_lot;



														}
										$regel_.= "<td>$lotsstr</td>";
										$regel_.= "<td>$options_</td>";

										$regel_.= "</tr>";

										$antwoord.= PHP_EOL.$regel_;					
								}


								$OUTPUT_VAR=$antwoord;

							break;
							case "save-load-toestemming":

									$AUTHID = -1;

									$xml = simplexml_load_string($_POST['sync_data']);

									$auction_id = trim($xml->wrapper->auction);
									$buyer_id = trim($xml->wrapper->buyerid);
									$transport_company = trim($xml->wrapper->transportcompany);
									$transport_driver = trim($xml->wrapper->transportdriver);
									$transport_plates = trim($xml->wrapper->driverplates);

									$xml_lots = ($xml->wrapper->lots->asXML());

									$sql = "INSERT INTO auctions_authorizations (auctionid,stamp,buyerid,transportcompany,transportdriver,transportplates,lots_xml) ";
									$sql.= " VALUES ('".$auction_id."',now(),'".$buyer_id."','".$transport_company."','".$transport_driver."','".$transport_plates."','".$xml_lots."')"; 


									if ($mysql_connectie->query($sql)) {
											$AUTHID = $mysql_connectie->lastInsertId();
									} else {
												 
									}
									
									$OUTPUT_VAR = $AUTHID;
								

							break;
							case "save-task":
							


									$xml = simplexml_load_string($_POST['sync_data']);

									 
									$titel = trim($xml->titel);
									$omschr =  urlencode(trim($xml->descr));
									$prio = trim($xml->prio);
						 			$voor = trim($xml->voor);
 

									$sql = "INSERT INTO opdrachten (geplaatst,titel,omschrijvingopdracht,prio,voor) ";
									$sql.= " VALUES (now(),'".$titel."','".$omschr."','".$prio."','".$voor."')"; 


									if ($mysql_connectie->query($sql)) {
												$OUTPUT_VAR="OK";	
										} else {
												$OUTPUT_VAR="FAIL| ".$mysql_connectie->errorInfo()[2];	
									  }




							break;
							case "get-last-sync":

									$antwoord = "";
									$sql_mag = "SELECT * from systeemwaardes where id='1' LIMIT 1";
									foreach($mysql_connectie->query($sql_mag) as $pd) {
											$a_l = $pd['lastsync'];
											$pr = explode(" ", $a_l);

											$datu = $pr[0];
											$tijd= $pr[1];
											$tijd_kort = "";
											$datum_kort = explode("-", $datu)[0]."-".explode("-", $datu)[1];
											//$tijd_kort = explode(":", $tijd).":".explode(":", $tijd)[1];
 											$antwoord=$datum_kort." ".$tijd_kort;

									}
									$OUTPUT_VAR=$antwoord;


							break;
							case "get-tree-for-data-sync":

									$antwoord = "";
									$sql_mag = "SELECT * from machines_categories order by volgnr asc";
									foreach($mysql_connectie->query($sql_mag) as $pd) {
											$a_l = $pd['url'];
 											$antwoord.= "<li><a href=\"".$pd['url']."\">".$pd['catname']."</a></li>";
									}
									$OUTPUT_VAR=$antwoord;

							break;
							case "get-transport":

									$antwoord = "";

									$sqlqstr="SELECT distinct(box16) FROM cmr_documents order by box16 asc";

									foreach($mysql_connectie->query($sqlqstr) as $pd) {

										$var = str_replace("\n","eeeeeeeeee",trim(urldecode($pd['box16'])));

										$title = explode("eeeeeeeeee",$var)[0];

										//$var = str_replace("|||","\n",trim(urldecode($pd['box16'])));

										if($var != ""){

											//$antwoord.= $var."<br />";

											$antwoord.= "<li><a href=\"javascript:select_vv('".$title."','".($var)."');void(0);\">".$title."</a></li>";
										}
 											 
 


									}

									 



									$OUTPUT_VAR = $antwoord;


							break;
							case "achiveer-cmr";
									$id = trim($_GET['id']);	
									 
								 		$sql = "UPDATE cmr_documents SET inarchief='1' where id='".trim($id)."'";
										if ($mysql_connectie->query($sql)) {
												 
								    			 
											} else {
												 
								  		}
								  		$OUTPUT_VAR = "OK";
							break;
							case "get-cmr-data-on-id":

												$antwoord = "<xml>";

												$id = trim($_GET['id']);	
												$sql_mag = "SELECT * from cmr_documents where id='".trim($id)."' ";
																				 
												foreach($mysql_connectie->query($sql_mag) as $cmr) {

														$box1 =  $cmr['box1'];
														$box2 = $cmr['box2'];
														$box3 = $cmr['box3'];
														$box4 = $cmr['box4'];
														$box5 = $cmr['box5'];
														$box6 = $cmr['box6'];
														$box6b = $cmr['box6b'];
														$box13 = $cmr['box13'];
														$box16 = $cmr['box16'];
														$box19 =$cmr['box19'];
														$box21 = $cmr['box21'];
														$box22 = $cmr['box22'];



														$antwoord.= "<box1><![CDATA[".$box1."]]></box1>";
														$antwoord.= "<box2><![CDATA[".$box2."]]></box2>";
														$antwoord.= "<box3><![CDATA[".$box3."]]></box3>";
														$antwoord.= "<box4><![CDATA[".$box4."]]></box4>";
														$antwoord.= "<box5><![CDATA[".$box5."]]></box5>";
														$antwoord.= "<box6><![CDATA[".$box6."]]></box6>";
														$antwoord.= "<box6b><![CDATA[".$box6b."]]></box6b>";
														$antwoord.= "<box13><![CDATA[".$box13."]]></box13>";
														$antwoord.= "<box16><![CDATA[".$box16."]]></box16>";
														$antwoord.= "<box19><![CDATA[".$box19."]]></box19>";
														$antwoord.= "<box21><![CDATA[".$box21."]]></box21>";													
														$antwoord.= "<box22><![CDATA[".$box22."]]></box22>";

												}

												$antwoord.= "</xml>";

												$OUTPUT_VAR = $antwoord;


							break;
							case "get-cmr-table-overview":

									$antwoord = "";
									date_default_timezone_set('Europe/Amsterdam');

									$sqlqstr="SELECT * FROM cmr_documents WHERE inarchief <> '1' order by id desc";

									foreach($mysql_connectie->query($sqlqstr) as $pd) {

  	
 											$regel="<tr id=\"tr".$pd['id']."\">";

 											$regel.= "<td class=\"text-right\">M".trim((int)$pd['id'])."</td>";
 							 				$date=date_create($pd['geplaatst']);
 											$d=date_format($date,"d-m-Y");
 	 										$regel.= "<td NOWRAP>".$d."</td>";			

 
 											$opties = "<a class=\"btn btn-default\" href=\"/?action=proforma_&sa=create_cmr&cmr-edit=".trim($pd['id'])."\" title=\"Bewerken\">";
 											$opties.= "<span class=\"fa fa-pencil\"></span>";
 											$opties.= "</a>";
 											$regel.= "<td>".$opties."</td>";
 											$regel.= "<td>".trim(urldecode($pd['box2']))."</td>";


 											$opties = "<a class=\"btn btn-primary\" target=\"_blank\" href=\"/losse-cmr.php?cmr-id=".trim((int)$pd['id'])."\" title=\"Bekijk deze vrachtbrief\">";
 											$opties.= "Open CMR (PDF)";
 											$opties.= "</a>";


 											$regel.= "<td>".$opties."</td>";


 											$trash_link = "javascript:archiveer_cmr('".trim($pd['id'])."');void(0);";

 											$opties = "<a class=\"btn btn-default\"  href=\"".$trash_link."\" title=\"Archiveer vrachtbrief\">";
 											$opties.= "<span class=\"fa fa-trash\"></span>";
 											$opties.= "</a>";

 											$regel.= "<td>".$opties."</td>";

 											$regel.= "</tr>";
 
 											$antwoord.=$regel;

									}

									if(trim($antwoord) !=""){
										$antwoord = "<table class=\"table table-bordered table-hover\"><tbody>".$antwoord."</tbody></table>";
									}

									$OUTPUT_VAR = $antwoord;


							break;
							case "save-packinglist-data";
									$id = $_GET['id'];	
									$xml_p = $_POST['sync_data'];
								 		$sql = "UPDATE commercial_data SET packinglist_xml='".$xml_p."' where id='".$id."'";
										if ($mysql_connectie->query($sql)) {
												 
								    			 
											} else {
												 
								  		}
								  		$OUTPUT_VAR = "";

							break;
							case "get-packing-list";


									$id = $_GET['id'];

									$antwoord = "empty";

									$sqlqstr="SELECT * FROM commercial_data where id='".trim($id)."' and offline !='1' LIMIT 1";

									foreach($mysql_connectie->query($sqlqstr) as $pd) {



												if(trim($pd['packinglist_xml']) != ""){

														$antwoord = trim($pd['packinglist_xml']);

												} else {


														$antwoord = "<xml>";



														$SHIPPING_DATA = "<shipping>";
														$SHIPPING_DATA.= "	<enabled>1</enabled>";
														$SHIPPING_DATA.= "	<tos><![CDATA[]]></tos>";	
														$SHIPPING_DATA.= "	<booking><![CDATA[]]></booking>";														
														$SHIPPING_DATA.= "	<sealno><![CDATA[]]></sealno>";
														$SHIPPING_DATA.= "	<containerno><![CDATA[]]></containerno>";														
														$SHIPPING_DATA.= "	<vessel><![CDATA[]]></vessel>";														
														$SHIPPING_DATA.= "	<loading><![CDATA[]]></loading>";
														$SHIPPING_DATA.= "	<discharge><![CDATA[]]></discharge>";													
														$SHIPPING_DATA.= "</shipping>";


														$antwoord.= $SHIPPING_DATA;


														$B = simplexml_load_string($pd['buyer_xml']);
														$G = simplexml_load_string($pd['goods_xml']);

														$DESCR_ = "";
														$GOODS_MODEL="";$GOODS_MAKE="";$GOODS_SERIALS="";$GOODS_WEIGHT="";
														$REGELS_CONTENT = "";

																$REGELS_CONTENT.= "<regel>";
																		$REGELS_CONTENT.= "<col><![CDATA[]]></col>";
																		$REGELS_CONTENT.= "<col><![CDATA[Serial No]]></col>";
																		$REGELS_CONTENT.= "<col><![CDATA[".$GOODS_SERIALS."]]></col>";
																	$REGELS_CONTENT.= "</regel>";




														foreach($G->equipment as $ment){
																$f = $ment->title;
																$qty = $ment->qty;
																

															//	if(trim($f) != "" || trim($qty)==""){


															//	} else {



													 				foreach($ment->specification->spec as $spec)
																	{
																		$spec_name = trim($spec->name);
																		$spec_value= trim($spec->value);

																		if(strtolower($spec_name)=="make"){
																			$GOODS_MAKE = $spec_value; 
																		} 

																		if(strtolower($spec_name)=="model"){
																			$GOODS_MODEL = $spec_value;

																		} 

																		if(strtolower($spec_name)=="serial no." || strtolower($spec_name)=="serial"){	
																				$GOODS_SERIALS = $spec_value; 
																		}
																		if(strtolower($spec_name)=="weight" || strtolower($spec_name)=="serial"){	
																				$GOODS_WEIGHT = $spec_value; 
																		}


																	}

																	$DESCR_.= $qty. " x ".$f.": ".$GOODS_MAKE." ".$GOODS_MODEL;
																	$DESCR_.= "\nSerial No. ".$GOODS_SERIALS;
																	$DESCR_.="\nNet weight: ".$GOODS_WEIGHT. "Kg";
																	$DESCR_.="\nNumber of coli: ";
																	$DESCR_.="\nPacking: ";		
																	$DESCR_.="\n\nTerms of sale: ".$pd['tos']." ".$pd['tos_descr'];	



																	$REGELS_CONTENT = "";


																$REGELS_CONTENT.= "<regel>";
																		$REGELS_CONTENT.= "<col><![CDATA[".$qty. " x]]></col>";
																		$REGELS_CONTENT.= "<col><![CDATA[".$f.":".$GOODS_MAKE." ".$GOODS_MODEL."]]></col>";
																		$REGELS_CONTENT.= "<col><![CDATA[]]></col>";
																	$REGELS_CONTENT.= "</regel>";
															//}													

														}





																$REGELS_CONTENT.= "<regel>";
																		$REGELS_CONTENT.= "<col><![CDATA[]]></col>";
																		$REGELS_CONTENT.= "<col><![CDATA[Serial No]]></col>";
																		$REGELS_CONTENT.= "<col><![CDATA[".$GOODS_SERIALS."]]></col>";
																	$REGELS_CONTENT.= "</regel>";

																	$REGELS_CONTENT.= "<regel>";
																		$REGELS_CONTENT.= "<col><![CDATA[]]></col>";
																		$REGELS_CONTENT.= "<col><![CDATA[Net weight]]></col>";
																		$REGELS_CONTENT.= "<col><![CDATA[".$GOODS_WEIGHT."]]></col>";
																	$REGELS_CONTENT.= "</regel>";

																	$REGELS_CONTENT.= "<regel>";
																		$REGELS_CONTENT.= "<col><![CDATA[]]></col>";
																		$REGELS_CONTENT.= "<col><![CDATA[Number of coli]]></col>";
																		$REGELS_CONTENT.= "<col><![CDATA[1]]></col>";
																	$REGELS_CONTENT.= "</regel>";

																	$REGELS_CONTENT.= "<regel>";
																		$REGELS_CONTENT.= "<col><![CDATA[]]></col>";
																		$REGELS_CONTENT.= "<col><![CDATA[Packing]]></col>";
																		$REGELS_CONTENT.= "<col><![CDATA[UNPACKED / SELF DRIVING]]></col>";
																	$REGELS_CONTENT.= "</regel>";

																	$REGELS_CONTENT.= "<regel>";
																		$REGELS_CONTENT.= "<col><![CDATA[]]></col>";
																		$REGELS_CONTENT.= "<col><![CDATA[.]]></col>";
																		$REGELS_CONTENT.= "<col><![CDATA[.]]></col>";
																	$REGELS_CONTENT.= "</regel>";

																	$REGELS_CONTENT.= "<regel>";
																		$REGELS_CONTENT.= "<col><![CDATA[]]></col>";
																		$REGELS_CONTENT.= "<col><![CDATA[Terms of sale]]></col>";
																		$REGELS_CONTENT.= "<col><![CDATA[EXW Dolderweg 44, 8331LL Steenwijk - The Netherlands]]></col>";
																	$REGELS_CONTENT.= "</regel>";
																	

														$co = $B->naambv;
														
														$t=$B->street;if(trim($t) != "" && trim($t) != "0"){$co.= "\n".$t;}
														$t=$B->postal;if(trim($t) != "" && trim($t) != "0"){$co.= "\n".$t;}
														$t=$B->city;if(trim($t) != "" && trim($t) != "0"){$co.= "\n".$t;}
														$t=$B->country;if(trim($t) != "" && trim($t) != "0"){$co.= ", ".$t;}
														//$t=$B->vat;if(trim($t) != "" && trim($t) != "0"){$co.= "\n".$t;}

														$antwoord.= "<date><![CDATA[".$pd['ci_date']."]]></date>";
														$antwoord.= "<listof><![CDATA[Invoice ".$pd['ci_no']."]]></listof>";
														$antwoord.= "<from><![CDATA[".$CLIENT_ADDRESS_YARD."]]></from>";	
														$antwoord.= "<to><![CDATA[".$pd['tos_descr']."]]></to>";	
														$antwoord.= "<sender><![CDATA[MAX MACHINERY EQUIPMENT\n".$CLIENT_ADDRESS_YARD."]]></sender>";	
														$antwoord.= "<co><![CDATA[".$co."]]></co>";
														$antwoord.= "<regels>".$REGELS_CONTENT."</regels>";
														$antwoord.= "<descr><![CDATA[".$DESCR_."]]></descr>";

														

														$antwoord.= "</xml>";



											}

											break;

									}

									



									$OUTPUT_VAR = $antwoord;





							break;
							case "get-invoice-data-for-cmr":

									$invoice = $_GET['id'];

									$antwoord = "<xml>";

									$sqlqstr="SELECT * FROM proforma_data where id='".trim($invoice)."' and offline !='1' ";

									foreach($mysql_connectie->query($sqlqstr) as $pd) {

											$antwoord.= $pd['buyer_xml'];
 											$antwoord.= $pd['goods_xml'];

 											$antwoord.= "<data>";

 											$antwoord.= "<tos><![CDATA[".$pd['tos']."]]></tos>";
 											$antwoord.= "<tos_descr><![CDATA[".$pd['tos_descr']."]]></tos_descr>";				


 											$antwoord.= "</data>";


									}

									$antwoord.= "</xml>";



									$OUTPUT_VAR = $antwoord;


							break;
							case "get-proforma-invoices-shortlist":

													$antwoord = "";
													$sqlqstr="SELECT * FROM proforma_data where offline != '1' order by geplaatst desc";

													foreach($mysql_connectie->query($sqlqstr) as $pd) {
																$po = str_replace("\"", "", trim($pd['invoice_buyer_name']));
																$txt = trim($pd['invoice_no'])."_".trim($pd['id'])."_".trim($po);
							 									$antwoord.= "<li><a href=\"javascript:select_pi('".($txt)."');void(0);\">".$txt."</a></li>";

													} 

													$OUTPUT_VAR = $antwoord;





							break;
							case "get-diesel":




													$date = new DateTime();
													$date->add(DateInterval::createFromDateString('yesterday'));
													$gisteren =  $date->format("d-m-Y");
													$sqlqstr="SELECT * FROM dieselprijzen where datum='".$gisteren."'";
													$gister_diesel ="";
													$gister_dollar = "";
													foreach($mysql_connectie->query($sqlqstr) as $pd) {
															 
															$gister_dollar = $pd['prijs_dollar'];
												 			$gister_diesel  = $pd['prijs_eurocenten'];
													} 

													$datum = date("d-m-Y");
													$sqlqstr="SELECT * FROM dieselprijzen where datum='".$datum."'";
													$dieselp0 = 0;
													$dollarp0 = 0;
													$dieselp = "<b>".$datum." (?)</b>";
													$dollarp = "<b>?</b>";
													$liters_in_detank = 435;
													foreach($mysql_connectie->query($sqlqstr) as $pd) {
															$volle_tank = $pd['prijs_eurocenten']*$liters_in_detank;
															$dollarp0=$pd['prijs_dollar'];
															$dollarp= "<span class=\"fa fa-dollar\"></span>&nbsp;&nbsp;".$pd['prijs_dollar'];
															$dieselp0 = $pd['prijs_eurocenten'];
												 			$dieselp = "<img src=\"src/fuel-icon.svg\" style=\"width:11px;\" />&nbsp;&nbsp;&euro;&nbsp;".$pd['prijs_eurocenten'];
													} 
 

													if($dieselp0 > $gister_diesel){
														// diesel gestegen 
														$diesel_status = "<i class=\"fa fa-sort-up\" style=\"color:red;\"></i>";
													} else {
														// diesel gedaald
														$diesel_status = "<i class=\"fa fa-sort-down\" style=\"color:lightgreen;\"></i>";
													}


													if($dieselp0 == $gister_diesel){
														$diesel_status = "-";
													}


													if($dollarp0 > $gister_dollar){
														// dollar gestegen 
														$dollar_status = "<i class=\"fa fa-sort-up\" style=\"color:lightgreen;\"></i>";
													} else {
														// dollar gedaald
														$dollar_status = "<i class=\"fa fa-sort-down\" style=\"color:red;\"></i>";
													}


													if($dieselp0 == $gister_diesel){
														$diesel_status = "-"; // gelijk gebleven
													}

													if($dollarp0 == $gister_dollar){
														$dollar_status = "-"; // gelijk gebleven
													}


													if($dieselp0==0 && $dollarp0==0){
														$dollar_status="";$diesel_status="";
													}


													$OUTPUT_VAR =$dieselp."&nbsp;".$diesel_status."&nbsp;&nbsp;|&nbsp;&nbsp;".$dollarp."&nbsp;".$dollar_status;
									 

							break;
							case "get-personal-links":

													$sqlqstr="SELECT * FROM personal_links where userid='".trim($_GET['userid'])."'";
												




													$lijst = "<div class=\"list-group\">";


													foreach($mysql_connectie->query($sqlqstr) as $pd) {

														$lijst.= "  <a href=\"".urldecode($pd['url'])."\" target='_blank' type=\"button\" class=\"list-group-item\">";

														$lijst.= "<img src='".$pd['fav']."' class=\"vafnoci\" border=0 />&nbsp;&nbsp;";
														$lijst.= $pd['titel'];
														$lijst.= "</a>";
													} 


													$lijst.= "</div>";		
													$OUTPUT_VAR = $lijst;
									 

							break;
							case "add_personal_url":

								$antwoord = "";
									$userid=1;

									if(isset($_GET['link']) && trim($_GET['link']) != ""){
										$title = get_title_for_personal_link(trim($_GET['link']));
										$favicon = download_fav(trim($_GET['link']));
										$url = urlencode(trim($_GET['link']));


										$sql = "INSERT INTO personal_links (userid,fav,titel,url) ";
										$sql.= " VALUES ('".$userid."','".$favicon."','".$title."','".$url."')"; 


											if ($mysql_connectie->query($sql)) {
														//$last_id = $mysql_connectie->lastInsertId();
										    			$antwoord = "OK";
												} else {
														$antwoord="FAIL| ".$mysql_connectie->errorInfo()[2];	
										  }

									} else {
										
										$antwoord = "FAIL|NO URL";
									}
//personal_links


									$OUTPUT_VAR = $antwoord;
							break;
							case "get-years":













									$OUTPUT_VAR = "get-years";


							break;
							case "save_cmr_los":
									$xml = simplexml_load_string($_POST['sync_data']);

									 
									$box1 = trim($xml->box1);
									$box2 = trim($xml->box2);
									$box3 = trim($xml->box3);
									$box4 = trim($xml->box4);
									$box5 = trim($xml->box5);
									$box6 = trim($xml->box6);
									$box6b = trim($xml->box6b);
									$box13 = trim($xml->box13);
									$box16 = trim($xml->box16);
									$box19 = trim($xml->box19);
									$box21 = trim($xml->box21);
									$box22 = trim($xml->box22);

									$EDIT_OR_ADD = trim($xml->editid);

									$geplaatst = $datetime;//
									$lmx = trim($_POST['sync_data']);

									if(trim($EDIT_OR_ADD) == ""){
										/*
											NIEUW CMR OPSLAAN
										*/
										$sql = "INSERT INTO cmr_documents (xmlfeed,geplaatst,box1,box2,box3,box4,box5,box6,box6b,box13,box16,box19,box21,box22) ";
										$sql.= " VALUES ('".$lmx."','".$geplaatst."','".$box1."','".$box2."','".$box3."','".$box4."','".$box5."','".$box6."','".$box6b."','".$box13."','".$box16."','".$box19."','".$box21."','".$box22."')"; 
									} else {


										$sql = "UPDATE cmr_documents SET ";
										$sql.= "xmlfeed='".$lmx."', ";
										$sql.= "box1='".$box1."', ";
										$sql.= "box2='".$box2."', ";
										$sql.= "box3='".$box3."', ";
										$sql.= "box4='".$box4."', ";
										$sql.= "box5='".$box5."', ";
										$sql.= "box6='".$box6."', ";
										$sql.= "box6b='".$box6b."', ";
										$sql.= "box13='".$box13."', ";
										$sql.= "box16='".$box16."', ";
										$sql.= "box19='".$box19."', ";
										$sql.= "box21='".$box21."', ";
										$sql.= "box22='".$box22."' ";
										$sql.= "WHERE id=".$EDIT_OR_ADD."  ";

									}

								


									if ($mysql_connectie->query($sql)) {

												if(trim($EDIT_OR_ADD) == ""){
													/*
														NIEUW CMR OPSLAAN
													*/
													$last_id = $mysql_connectie->lastInsertId();
								    				$OUTPUT_VAR=$last_id;	
												} else {

													$OUTPUT_VAR=$EDIT_OR_ADD;	
												}



										} else {
												$OUTPUT_VAR="FAIL| ".$mysql_connectie->errorInfo()[2];	
								  }




							break;
							case "get-latest-commercial-invoice-number";


													$CURRENT_YEAR  = trim(date("Y"));
 
													$CRNT = substr($CURRENT_YEAR, strlen($CURRENT_YEAR)-2,2)."".date("m");

													$sqlqstr="SELECT ci_no FROM commercial_data where (offline != '1') AND (ci_date LIKE '%".$CURRENT_YEAR."') order by ci_no desc LIMIT 1";
													$OUTPUT_VAR = $CRNT."001";
													foreach($mysql_connectie->query($sqlqstr) as $pd) {
														$c = ($pd['ci_no']);	

														$c= substr($c, strlen($c)-3,3);
														$c= (int)$c+1;

														$out_code = $c;

														if(strlen($c)==1){
															$out_code = "00".$c;
														}

														if(strlen($c)==2){
															$out_code = "0".$c;
														}


														$OUTPUT_VAR = $CRNT.$out_code;
		
														break;
													} 

							break;	
							case "get-latest-invoice-number";


													$CURRENT_YEAR  = date("Y");

													$EERSTE_DAG = $CURRENT_YEAR."-01-01";
													$LAATSTE_DAG =  $CURRENT_YEAR."-12-31";

													$SCOPE = $EERSTE_DAG." ".$LAATSTE_DAG;


													$sqlqstr="SELECT invoice_no FROM proforma_data where (offline != '1') AND (geplaatst BETWEEN '".$EERSTE_DAG."' and '".$LAATSTE_DAG."') order by invoice_no desc LIMIT 1";
													$OUTPUT_VAR = "001";
													foreach($mysql_connectie->query($sqlqstr) as $pd) {
														$c = ($pd['invoice_no'])+1;	

														$out_code = $c;

														if(strlen($c)==1){
															$out_code = "00".$c;
														}

														if(strlen($c)==2){
															$out_code = "0".$c;
														}

														$OUTPUT_VAR = $out_code;
														break;
													} 

							break;						
							case "get-relations-from-invoices":

									if(isset($_GET['s'])){

										// HAALT EEN LIJST MET BUYERS OP AAN DE HAND VAN QUERY	

										$str = $_GET['s'];
										$array = array();		
										
										$sqlqstr="SELECT * FROM proforma_data where invoice_buyer_name LIKE '%{$str}%' GROUP BY invoice_buyer_name";

										foreach($mysql_connectie->query($sqlqstr) as $relation) {
											$buyer_name = $relation['id']." / ".$relation['invoice_buyer_name'];
											$array[] = $buyer_name;
										}	

										$OUTPUT_VAR = json_encode($array);


									}


									if(isset($_GET['id'])){

										// HAAL GEGEVENS OP VAN GESELECTEERDE BUYER


										$sqlqstr="SELECT * FROM proforma_data where id='".trim($_GET['id'])."' LIMIT 1";

										foreach($mysql_connectie->query($sqlqstr) as $relation) {
											$OUTPUT_VAR= $relation['buyer_xml'];
											
											break;
										}	

									}




							break;
							case "save-alarm":

								$taskid = trim($_GET['taskid']);
								$hour = trim($_GET['hour']);
								$minuut = trim($_GET['minutes']);
								$datum = trim($_GET['date']);
 
								$sql = "UPDATE callbacks SET ";
								$sql.= "alarm_datum='".$datum."', ";
								$sql.= "alarm_uur='".$hour."', ";
								$sql.= "alarm_minuut='".$minuut."' ";
								$sql.= "WHERE id=".$taskid."  ";

								if ($mysql_connectie->query($sql)) {
								    			$OUTPUT_VAR="OK";	
										} else {
												$OUTPUT_VAR="FAIL| ".$mysql_connectie->errorInfo()[2];	
								  }

							break;
							case "save-follow-up":

								$taskid = trim($_GET['taskid']);
								$tekst = trim($_GET['tekst']);

								$bel_xml =  $tekst;
								 
						
								$tijd = date("H:i");
								$FOUND = 0;
								$sql = "INSERT INTO callbacks (datum,tijd,bel_xml,klaar,taskid,is_followup) VALUES (now(),'".$tijd."','".trim($bel_xml)."','0','".$taskid."','1')"; 


								if ($mysql_connectie->query($sql)) {
								    			$OUTPUT_VAR="OK";	
										} else {
												$OUTPUT_VAR="FAIL| ".$mysql_connectie->errorInfo()[2];	
								  }




							break;
							case "get-proforma-invoices-list":


													$jaar = trim($_GET['y']);


													$antwoord = "";
													$sqlqstr="SELECT * FROM proforma_data where offline='0' and invoice_date LIKE '%".$jaar."' order by geplaatst desc";

													foreach($mysql_connectie->query($sqlqstr) as $pd) {

														//$options= "<a href=\"/?action=proforma_&sa=create_proforma&edit=true&id=".$pd['id']."\"class=\"btn btn-default btn-sm glyphicon glyphicon-pencil\"></a>&nbsp;";	
														//$options.= "<button class=\"btn btn-danger btn-sm glyphicon glyphicon-trash\" value=\"\"></button>";

														$updated_on = "";
														if(trim($pd['laatste_aanpassing']) != ""){
															$updated_on = "<br /><small><i>Gewijzigd: ".trim($pd['laatste_aanpassing'])."</i></small>";
														}


														$GOODS = $pd['goods_xml'];

														$mx = simplexml_load_string($GOODS);




														// show equipment in this invoice -------------------------------
													 
														$GDS_XML = $mx;
														$descr_goods = "";
														foreach($GDS_XML->equipment as $goods_item)
														{
																foreach($goods_item->specification->spec as $spec)
																{
																	$temp_str = "";
																	$spec_name = trim(strtolower($spec->name));
																	$spec_value= trim(strtoupper($spec->value));
																	if(trim($spec_name) !="" && trim($spec_value) !=""){
																			if(trim(strtolower($spec_name))=="make" || trim(strtolower($spec_name))=="merk"){
																				$temp_str = $spec_value;
																			}
																			if(trim(strtolower($spec_name))=="model"){
																				$temp_str.= " - ".$spec_value;
																			}
																	} 

																	if(trim($temp_str) != ""){
																			if(trim($descr_goods) == ""){
																				$descr_goods = $temp_str;
																			} else {
																				$descr_goods.= " ".$temp_str;
																			}
																	}
									 							}

									 							if(trim($descr_goods)==""){
									 								$equipment_title = trim($goods_item->title);
									 								$descr_goods = "".$equipment_title."";
									 							}
														}
														if(trim($descr_goods)==""){$descr_goods="<i>- Service invoice -</i>";}
														$goods_descr = "- <b>".$descr_goods."</b>";
														// ---------------------------------------------------------------










													 	$tooltip_ = "";

														foreach($mx->equipment as $ment){
																$f = $ment->title;


																if($tooltip_==""){
																	$tooltip_ = $f;
																} else {
																	$tooltip_ .= " en ".$f;
																}		


														}
														unset($mx);


														$INVOICE_LINK = "<a href=\"/losse-cmr.php?proforma-id=".$pd['id']."&hidebg=1\"  title=\"".$tooltip_."\" target=\"_blank\">".$pd['invoice_buyer_name']." - ".$pd['invoice_buyer_country']."</a>";
														$INVOICE_LINK.= "<br />".$goods_descr;

														$options = "<a class='btn btn-default btn-md' href=\"/losse-cmr.php?proforma-id=".$pd['id']."&hidebg=1\" target='_blank'>";
														$options.= "Blanco";
														$options.= "</a>";
														$options.= "&nbsp;&nbsp;&nbsp;";
														$options.= "<a class='btn btn-success btn-md' href=\"/losse-cmr.php?proforma-id=".$pd['id']."\" target='_blank'>";
														$options.= "Stamped";
														$options.= "</a>";

														$EDIT_BTNS = "<a title=\"Bewerk deze proforma\" href=\"/?action=proforma_&sa=create_proforma&edit=true&id=".$pd['id']."\" class=\"btn btn-default btn-sm\"><span class=\"fa fa-pencil\"></span></a>";

														$DEL_BTNS= "<center><a title=\"Verwijder\" href=\"javascript:remove_pi('".$pd['id']."');void(0);\" class=\"btn btn-default\"><span class=\"fa fa-trash\"></span></a></center>";

														$c = "pi".$pd['id'];	
														$antwoord.= "<tr class='".$c."''><td class=\"text-right\">".$pd['invoice_no']."</td><td>".$EDIT_BTNS."</td>";
														$antwoord.= "<td>".$INVOICE_LINK."</td><td>".$pd['geplaatst'];
														$antwoord.="</td><td>".$DEL_BTNS."<td>".$options."</td></tr>";



														

													} 

													$OUTPUT_VAR = $antwoord;


							break;
							case "get-commercial-invoices-list":


													$jaar = trim($_GET['y']);

													$antwoord = "";

														$sqlqstr="SELECT * FROM commercial_data where offline != '1' AND ci_date LIKE '%".$jaar."' order by id desc";
													foreach($mysql_connectie->query($sqlqstr) as $pd) {

														//$options= "<a href=\"/?action=proforma_&sa=create_proforma&edit=true&id=".$pd['id']."\"class=\"btn btn-default btn-sm glyphicon glyphicon-pencil\"></a>&nbsp;";	
														//$options.= "<button class=\"btn btn-danger btn-sm glyphicon glyphicon-trash\" value=\"\"></button>";

														$PROFORMA_NO = trim($pd['pi_no']);
														$PROFORMA_YEAR = "20".substr(trim($pd['ci_no']),0,2);
														$PROFORMA ="<br />Proforma: ".$PROFORMA_NO."-".$PROFORMA_YEAR;
														$PROFORMA_ID = 0;



														$get_pi_id ="SELECT * FROM proforma_data where invoice_no = '".$PROFORMA_NO."' AND invoice_date LIKE '%-".$PROFORMA_YEAR."' LIMIT 1";		
														foreach($mysql_connectie->query($get_pi_id) as $piid) {
															$PROFORMA_ID = $piid['id'];
															break;
														}

														if(trim($PROFORMA_ID) == "0"){
															// niet gevonden
															$PROFORMA_LINK = "";
															$PROFORMA_SHOW = "";

														} else {

															$PROFORMA_LINK = "<a href='/?action=proforma_&sa=create_proforma&edit=true&id=".$PROFORMA_ID."' title='Bewerk de gekoppelde proforma' target='_blank'>";
															$PROFORMA_LINK.= "Bewerk Porforma No ".$PROFORMA_NO;
															$PROFORMA_LINK.= "</a>";

															$PROFORMA_SHOW = "<a href='/losse-cmr.php?proforma-id=".$PROFORMA_ID."&hidebg=1' title='Bekijk de gekoppelde proforma' target='_blank'>";
															$PROFORMA_SHOW.= "Bekijk Porforma No ".$PROFORMA_NO;
															$PROFORMA_SHOW.= "</a>";


														}

														$PROFORMA = "<br />".$PROFORMA_LINK;


														// show equipment in this invoice -------------------------------
														$GOODS_ = $pd['goods_xml'];
														$GDS_XML = simplexml_load_string($GOODS_);
														$descr_goods = "";
														foreach($GDS_XML->equipment as $goods_item)
														{


															if($goods_item->specification->spec){


																foreach($goods_item->specification->spec as $spec)
																{
																	$temp_str = "";
																	$spec_name = trim(strtolower($spec->name));
																	$spec_value= trim(strtoupper($spec->value));
																	if(trim($spec_name) !="" && trim($spec_value) !=""){
																			if(trim(strtolower($spec_name))=="make" || trim(strtolower($spec_name))=="merk"){
																				$temp_str = $spec_value;
																			}
																			if(trim(strtolower($spec_name))=="model"){
																				$temp_str.= " - ".$spec_value;
																			}
																	} 

																	if(trim($temp_str) != ""){
																			if(trim($descr_goods) == ""){
																				$descr_goods = $temp_str;
																			} else {
																				$descr_goods.= " ".$temp_str;
																			}
																	}
									 							}

															}	

									 							if(trim($descr_goods)==""){
									 								$equipment_title = trim($goods_item->title);
									 								$descr_goods = "".$equipment_title."";
									 							}



														}
														if(trim($descr_goods)==""){$descr_goods="<i>- Service invoice -</i>";}
														$goods_descr = "- <b>".$descr_goods."</b>";
														// ---------------------------------------------------------------

 

    

  



														$options = "<div class=\"btn-group\"><button type=\"button\" class=\"btn btn-default dropdown-toggle\" data-toggle=\"dropdown\" aria-haspopup=\"true\" aria-expanded=\"false\">";
														$options.= "Acties <span class=\"caret\"></span></button><ul class=\"dropdown-menu\">";


														$LINK= "losse-cmr.php?cci=1&pi=".$pd['pi_no']."&hidebg=1&cino=".$pd['ci_no']."&cidate=".$pd['ci_date']."&id_=".$pd['id'];
														$options.= "<li><a  title=\"Toon invoice pdf\" href=\"".$LINK."\" target='_blank' >";
														$options.= "Blanco Invoice";
														$options.= "</a></li>";

														$LINK= "losse-cmr.php?cci=1&pi=".$pd['pi_no']."&cino=".$pd['ci_no']."&cidate=".$pd['ci_date']."&id_=".$pd['id']."";
														$options.= "<li><a  title=\"Toon invoice pdf\" href=\"".$LINK."\" target='_blank' >";
														$options.= "Invoice";
														$options.= "</a></li>";


														$LINK= "/losse-cmr.php?build_import_declaration=1&id_=".$pd['id'];
														$options.= "<li><a  title=\"Toon verklaring BTW/VAT pdf\" href=\"".$LINK."\" target='_blank'>";
														$options.= "VAT Declaration";
														$options.= "</a></li>";

													


													 
														$options.= "<li><a  href=\"/?action=unload-form&id=".$pd['id']."\"  >";
														$options.= "Delivery";
														$options.= "</a></li>";
 
														$options.= "<li role=\"separator\" class=\"divider\"></li>";

														if(trim($PROFORMA_LINK) != ""){
														$options.= "<li>".$PROFORMA_LINK."</li>";
														$options.= "<li>".$PROFORMA_SHOW."</li>";															
														}



															$options.= "</ul></div>&nbsp;&nbsp;";		 
														
														  		

														if(trim($pd['packinglist_xml']) != ""){

															$LINK= "?action=create-packinglist&id_=".$pd['id'];	
	 														$options.= "<div class=\"dropdown\" style=\"float:left;margin-left:4px;\"><button class=\"btn btn-default dropdown-toggle\" type=\"button\" id=\"dropdownMenu1\" data-toggle=\"dropdown\" aria-haspopup=\"true\" aria-expanded=\"true\">";
															$options.= "Packinglist <span class=\"caret\"></span></button><ul class=\"dropdown-menu\" aria-labelledby=\"dropdownMenu1\">";
															$options.= "<li><a href=\"/losse-cmr.php?packing-list=1&id=".$pd['id']."\" target=\"_blank\">Open PDF</a></li>";

															$options.= "<li><a href=\"/losse-cmr.php?packing-list=1&id=".$pd['id']."&stamped=1\" target=\"_blank\">Open PDF (Op briefpapier)</a></li><li><a href=\"".$LINK."\">Bewerken</a></li>";

															$options.= "</ul></div>";




														} else {
															$LINK= "?action=create-packinglist&id_=".$pd['id'];	
															$options.= "<a class='btn btn-default ' title=\"Maak / bekijk packinglist\" href=\"".$LINK."\" style=\"float:left;margin-left:4px;\">";

															$options.= "Packinglist";
															$options.= "</a>";
												 


														}
 



														


												 
														$LINK= "losse-cmr.php?cci=1&pi=".$pd['pi_no']."&hidebg=1&cino=".$pd['ci_no']."&cidate=".$pd['ci_date']."&id_=".$pd['id'];
														$COMMERCIAL_INVOICE_LINK = "<a  title=\"Toon invoice pdf\" href=\"".$LINK."\" target='_blank' style=\"float:left;margin-left:4px;\">";
														$COMMERCIAL_INVOICE_LINK.= $pd['invoice_buyer_name']." - ".$pd['invoice_buyer_country'];
														$COMMERCIAL_INVOICE_LINK.= "</a>";

 

														$DEL_BTNS= "<center><a title=\"Verwijder\" href=\"javascript:remove_ci('".$pd['id']."');void(0);\" class=\"btn btn-default\"><span class=\"fa fa-trash\"></span></a></center>";

														$c = "pi".$pd['id'];	
														$antwoord.= "<tr class='".$c."''><td class=\"text-right\">".$pd['ci_no']."</td>";
														$antwoord.= "<td>".$COMMERCIAL_INVOICE_LINK."<br />".$goods_descr."</td><td NOWRAP>".$pd['ci_date']."</td><td>".$DEL_BTNS."</td><td>".$options."</td></tr>";

 

													} 

													$OUTPUT_VAR = $antwoord;


							break;
							case "get-pi-data":

								$ID = trim($_GET['id']);


									$sqlqstr="SELECT * FROM proforma_data where id='".$ID."' LIMIT 1";

									$LMX = "<xml>";

									foreach($mysql_connectie->query($sqlqstr) as $pi) {


											$BUYER_ = $pi['buyer_xml'];
											$GOODS_ = $pi['goods_xml'];

											$LMX.= "<invoiceno><![CDATA[".$pi['invoice_no']."]]></invoiceno>";
											$LMX.= "<invoicedate><![CDATA[".$pi['invoice_date']."]]></invoicedate>";
											$LMX.= "<invoicevat><![CDATA[".$pi['vat']."]]></invoicevat>";
											$LMX.= "<invoicetos><![CDATA[".$pi['tos']."]]></invoicetos>";
											$LMX.= "<invoicetosdescr><![CDATA[".$pi['tos_descr']."]]></invoicetosdescr>";

											$LMX.= "<invoicecurrency><![CDATA[".$pi['currency']."]]></invoicecurrency>";
											$LMX.= "<downpaymentpercentage><![CDATA[".$pi['downpaymentpercentage']."]]></downpaymentpercentage>";

											$LMX.= "<downpaydays><![CDATA[".$pi['downpaydays']."]]></downpaydays>";
											$LMX.= "<balancedays><![CDATA[".$pi['balancedays']."]]></balancedays>";
											$LMX.= "<typeterms><![CDATA[".$pi['typeterms']."]]></typeterms>";
											$LMX.= "<invoice_type><![CDATA[".$pi['invoice_type']."]]></invoice_type>";
											$LMX.= "<addterms><![CDATA[".$pi['additional_payment_term']."]]></addterms>";
											$LMX.= "<dpmoney><![CDATA[".$pi['downpayment_money']."]]></dpmoney>";
											$LMX.= "<typeterms3><![CDATA[".$pi['typeterms_add_3']."]]></typeterms3>";
											$LMX.= "<paycss><![CDATA[".$pi['payment_css']."]]></paycss>";



											if(trim($pi['taal'])==""){
												$taal = "EN";
											} else {
												$taal = strtoupper(trim($pi['taal']));
											}

											$LMX.= "<invoice_lang><![CDATA[".$taal."]]></invoice_lang>";

											$LMX.= 	$BUYER_;
											
											$LMX.= 	$GOODS_;								
											 

									}	

									$LMX.= "</xml>";

								$OUTPUT_VAR = $LMX;



							break;
							case "get-kosten":
								$stamp = trim($_GET['q']);
						
								$outp = "none";

								$sqlqstr="SELECT * FROM KOSTEN where maand='".$stamp."' LIMIT 1";

								foreach($mysql_connectie->query($sqlqstr) as $pi) {	

									$outp = $pi['kostendata'];
								}


								$OUTPUT_VAR=$outp;

							break;
							case "toggle-gereed":
								$id=trim($_GET['taskid']);
								$sqlqstr="SELECT * FROM callbacks where id = '".$id."' LIMIT 1";
								$GEREED_CURRENT = 0;
								foreach($mysql_connectie->query($sqlqstr) as $pi) {	
									$GEREED_CURRENT = $pi['gereed'];
									break;
								}


								if($GEREED_CURRENT==1){
									$GEREED_NIEUW = 0;
								} else {
									$GEREED_NIEUW = 1;
								}


								$sql = "UPDATE callbacks SET ";
								$sql.= "gereed='".$GEREED_NIEUW."' ";
								$sql.= "WHERE id='".$id."'  ";

								if ($mysql_connectie->query($sql)) {
								    			$OUTPUT_VAR="OK";	
										} else {
												$OUTPUT_VAR="FAIL| ".$mysql_connectie->errorInfo()[2];	
								  }

 



							break;
							case "get-terug-bel-afspraak":

								$ARCHIVED_ITEMS = false;
								if(isset($_GET['archive']) && trim($_GET['archive']) == "1"){
									$ARCHIVED_ITEMS = true;
								}


								$sqlqstr="SELECT * FROM callbacks where klaar <> '1' and is_followup ='0' order by id desc";

								if($ARCHIVED_ITEMS){
									$sqlqstr="SELECT * FROM callbacks where klaar = '1' and is_followup ='0' order by id desc";
								}


								$antwoord = "<xml>";


								foreach($mysql_connectie->query($sqlqstr) as $pi) {	


									$antwoord.= "<cb id='".$pi['id']."'>";
										$datu = date("d-m", strtotime($pi['datum']));
										$antwoord.= "<stamp><![CDATA[".$datu."]]></stamp>";
										$antwoord.= "<gereed><![CDATA[".trim( $pi['gereed'])."]]></gereed>";
										$antwoord.= $pi['bel_xml'];

										$antwoord.= "<followup>";

											$sqlqstr1="SELECT * FROM callbacks where klaar <> '1' and is_followup ='1' and taskid='".trim($pi['id'])."' order by id desc";



											foreach($mysql_connectie->query($sqlqstr1) as $fu) {	


													$antwoord.= "<fu>";
														$antwoord.= "<datum><![CDATA[".$fu['datum']."]]></datum>";
														$antwoord.= "<tijd><![CDATA[".$fu['tijd']."]]></tijd>";
														$antwoord.= "<txt><![CDATA[".$fu['bel_xml']."]]></txt>";

													$antwoord.= "</fu>";



											}

										$antwoord.= "</followup>";	


									$antwoord.= "</cb>";


								 
								}

								$antwoord.= "</xml>";


								$OUTPUT_VAR=$antwoord;

							break;
							case "hide-terug-bel-afspraak":


									$id = trim($_GET['id']);


									$sql = "UPDATE callbacks SET ";
									$sql.= "klaar='1' ";
									$sql.= "WHERE id='".$id."'  ";


								if ($mysql_connectie->query($sql)) {
								    			$OUTPUT_VAR="OK";	
										} else {
												$OUTPUT_VAR="FAIL| ".$mysql_connectie->errorInfo()[2];	
								  }



							break;
							case "get-data-workplanner-single-machine":
								// haalt alle informaite op van één machine 




										function zet_tijd_om($tijd){

											return number_format((int)$tijd/60, 2, ',', '')."";
										}

										$id_ = trim($_GET['id']);
										$antwoord = "null (".$id_.")";


										$TABLE_OVERVIEW = "OK";
										$TABLE_VIEW2 = ""; // overzicht/beknopte weergave van aantal uren



		
										$maanden_ = "Januari,Februari,Maart,April,Mei,Juni,Juli,Augustus,September,Oktober,November,December";

										if(isset($_GET['chosen-month'])){
											$CURRENT_MONTH = trim($_GET['chosen-month']);
										}else {
											$CURRENT_MONTH = date("m");
										}

										/*
										 machine info
										*/


										$sqlqstr="SELECT * FROM work_planner_machines where id='".$id_."' LIMIT 1";	 

										$x_machine_info = "<info>";
										$wasuren="-1";

										foreach($mysql_connectie->query($sqlqstr) as $m) {

												$wasuren = trim($m['estimate_hours']) ;

												$x_machine_info.= "<makemodel><![CDATA[".$m['makemodel']."]]></makemodel>";
												$x_machine_info.= "<year><![CDATA[".$m['year']."]]></year>";
												$x_machine_info.= "<serial><![CDATA[".$m['serial']."]]></serial>";
												$x_machine_info.= "<key><![CDATA[".$m['machinekey']."]]></key>";
												$x_machine_info.= "<id><![CDATA[".$id_."]]></id>";
												$x_machine_info.= "<maxhours>".$wasuren."</maxhours>";
												

												$MACHINE_KEY = $m['id'];
										}		

										$x_machine_info.= "</info>";


										/*
											///////////////////////////////////////////////////////
										*/


 												$MAANDEN__ = explode(",", $maanden_);

												$TABS_ = "<ul class=\"nav nav-tabs nav-justified\">";

												for($i=0;$i<count($MAANDEN__);$i++){
													$M = (int)$i+1;

													if($CURRENT_MONTH==$M){
														$C=" class=\"active\" ";
													}else{ 	
														$C= "  ";
													}
													$LINK_ = "/?action=workshop-machine-overview&m=".$id_."&chosen-month=".$M ;
													$TABS_.= "<li role=\"presentation\" ".$C."><a href=\"".$LINK_."\">".$MAANDEN__[$i]."</a></li>";

												}
 



												$TABLE_VIEW2 = "";

												if(trim($wasuren) == "0" || trim($wasuren) == "-1"){
													$wasuren_text = "<strong>niet ingesteld</strong>";
												} else {
													$wasuren_text = $wasuren;
												}

												$INFO_TABLE_ = "<table><tr><td>Maximaal aantal wasuren: ".$wasuren_text."</td></tr></table><hr />";


												$TABLE_OVERVIEW = $INFO_TABLE_."<table class=\"table table-bordered\">";

												$TABLE_OVERVIEW.= "<thead><th></th>";
												$READER_KEYS_ARRAY = "";

												$sqlqstr="SELECT * FROM work_planner_readers where offline != '1' ";	

													foreach($mysql_connectie->query($sqlqstr) as $m) {

																$TABLE_OVERVIEW.= "<th class=\"text-center\">".$m['readername']."</th>";

																$WAARDE = trim($m['id'])."#".trim($m['tarief']);
																if($READER_KEYS_ARRAY==""){
																	$READER_KEYS_ARRAY = $WAARDE;
																}else{
																	$READER_KEYS_ARRAY.= "|".$WAARDE;
																}
									 				}

												$TABLE_OVERVIEW.= "<th class=\"text-center\"><b><span style=\"color:black !important;\">Dag totaal</span></b></th></thead>";


												$TABLE_OVERVIEW.= "<tbody>";

												$READER_KEYS_ARRAY = explode("|", $READER_KEYS_ARRAY);

												$sqlqstr="SELECT distinct(dag) FROM work_planner_logboek where machinekey='".trim($MACHINE_KEY)."' ";	


												$STATION_TOT_ARRAY = array();

													$FOUND_ROWS=0;

													$HUIDIGE_CSS_MONTH = "";

													foreach($mysql_connectie->query($sqlqstr) as $d) {
														$TOT_PER_STATION = 0;
															$FOUND_ROWS=1;
																
															$RR=0;
															$MONTH_NUM_ARRAY = explode("-", trim($d['dag']));
															$RR = (int)$MONTH_NUM_ARRAY[1]-1;
															$MAANDEN__ = explode(",", $maanden_);
															$COLSPAN = (int)count($READER_KEYS_ARRAY)+1;
 
															$CL_NAME = strtolower($MAANDEN__[$RR]);

															if(trim($CL_NAME) != strtolower(trim($HUIDIGE_CSS_MONTH))){
																$HUIDIGE_CSS_MONTH = $CL_NAME;



																$TABLE_OVERVIEW.="<tr><td class=\"maand_td\" colspan=\"".$COLSPAN."\"><h5>".ucwords($CL_NAME)."</h5></td>";
																$TABLE_OVERVIEW.="<td class=\"text-center\"></td></tr>";
															}



															$TABLE_OVERVIEW.="<tr class=\"".strtolower($CL_NAME)."\">";
	 													
															$da = strtotime($d['dag']);
															$newformat = date('d-m-Y (D)',$da);
															$TABLE_OVERVIEW.="<td>".$newformat."</td>";	


															$DAGTOTAAL_MINUTEN = 0;


															for($i=0;$i<count($READER_KEYS_ARRAY);$i++){

																	$SPLIT = explode("#",$READER_KEYS_ARRAY[$i]);

																	$reader_key = trim($SPLIT[0]);
																	$reader_tarief = trim($SPLIT[1]);

																	if(!isset($STATION_TOT_ARRAY[$i])){
																		$STATION_TOT_ARRAY[$i]=0;
																	}

																	$sql_t = "SELECT * FROM work_planner_logboek where ";
																	$sql_t.= "machinekey='".trim($MACHINE_KEY)."' AND ";	 
																	$sql_t.= "dag='".trim($d['dag'])."' AND ";
																	$sql_t.= "userid='".$reader_key."' ";

																	$STATION_TOTAAL = 0;
																	$STATION_TARIEF_TOTAAL = 0;
																	foreach($mysql_connectie->query($sql_t) as $t) {
																		
																		$checkin_  = $t['checkin'];
																		$checkout_ = $t['checkout'];

																 

																		if(trim($checkin_) != "" && trim($checkout_) != ""){




																				$start_date = new DateTime($checkin_);
																				$since_start = $start_date->diff(new DateTime($checkout_));
																				$MINUTES = $since_start->i;
																				$MINUTES += $since_start->h * 60;
																				$STATION_TOTAAL += $MINUTES;
																			 	$SUB = $STATION_TOT_ARRAY[$i];
																				$STATION_TOT_ARRAY[$i]=(int)$SUB+(int)$MINUTES;
																		}
																	}

																$TABLE_OVERVIEW.= "<td class=\"text-center\">";

																if($STATION_TOTAAL > 0){

																	


																	$TABLE_OVERVIEW.= zet_tijd_om($STATION_TOTAAL);

																} else {
																	$TABLE_OVERVIEW.= "<span style=\"color:#ccc;\">X</span>";
																}

																$TABLE_OVERVIEW.= "</td>";

																$DAGTOTAAL_MINUTEN += $STATION_TOTAAL;

															}

														 
															$TABLE_OVERVIEW.= "<td class=\"text-center\" style=\"background:#f2f2f2 !important\"><b>".zet_tijd_om($DAGTOTAAL_MINUTEN)."</b></td>";	

														$TABLE_OVERVIEW.="</tr>\n";



													}
												$TABLE_OVERVIEW.= "<tr ><td class=\"text-center\"><b>Totaal per medewerker</b></td>";
												$TOTAAL_TOTAAL = 0;

												for($i=0;$i<count($STATION_TOT_ARRAY);$i++){
													$TOTAAL_TOTAAL += (int)$STATION_TOT_ARRAY[$i];
													if((int)$STATION_TOT_ARRAY[$i]>0){
														$R = "<b>".zet_tijd_om($STATION_TOT_ARRAY[$i])."</b>";
													}else {
														$R = "X";
													}
													$TABLE_OVERVIEW.= "<td class=\"text-center\" style=\"background:#f2f2f2 !important\">".$R."</td>";
												}

												$TABLE_OVERVIEW.= "<td class=\"text-center\" style=\"background:#f2f2f2 !important\"><h5 style='margin:0px !important;'><b>".zet_tijd_om($TOTAAL_TOTAAL)."</b></h5></td>";
												$TABLE_OVERVIEW.= "</tr>";	

												$TABLE_OVERVIEW.= "</tbody></table>";






												$LINK_ = "/?action=workshop-machine-overview&m=".$id_."&view=everything";
												$TABLE_VIEW2 = "<div class=\"jumbotron\"><p>Totaal aantal uren tot nu toe: <strong>".zet_tijd_om($TOTAAL_TOTAAL)."</strong></p>";
												$TABLE_VIEW2.= "<p><a class=\"btn btn-primary btn-lg\" href=\"".$LINK_."\" role=\"button\">Uren overzicht bekijken</a></p></div>";//


										/*
											//////////////////////////////////////////////////////
										*/

										if(trim($FOUND_ROWS)=="0"){
											$TABLE_OVERVIEW = "<br /><center>Niets gevonden. Er is nog niet eerder ingechecked op deze machine.</center>";
											$TABLE_VIEW2 = $TABLE_OVERVIEW;
										}




										$STYLE_1 = PHP_EOL."<style>".PHP_EOL;
										for($i=0;$i<count($MAANDEN__);$i++){
	  											//$STYLE_1.= ".".strtolower($MAANDEN__[$i])."{display:none !important;}".PHP_EOL;
										}

										$CURMO = (int)date("m")-1;
										//$STYLE_1.=".".strtolower($MAANDEN__[$CURMO])."{display:block !important;}".PHP_EOL;



										$STYLE_1.= "</style>";


										$TABLE_OVERVIEW.= $STYLE_1;



										if(isset($_GET['view'])){

											// specific view


											if(trim($_GET['view'])=="all"){

												$x_html_table = "<tbl><![CDATA[".urlencode($TABLE_VIEW2)."]]></tbl>";

											} else {

												$x_html_table = "<tbl><![CDATA[".urlencode($TABLE_OVERVIEW)."]]></tbl>";	
											}




										} else {


											// default view
											$x_html_table = "<tbl><![CDATA[".urlencode($TABLE_OVERVIEW)."]]></tbl>";

										}








									/*
										ALLE WERKSTATIONS	
									*/
									$sqlqstr="SELECT * FROM work_planner_readers";	

									$x_machine_stations = "<readers>";

									foreach($mysql_connectie->query($sqlqstr) as $m) {

										$x_machine_stations.= "<reader>";
										$x_machine_stations.= "<stationkey><![CDATA[".$m['readerkey']."]]></stationkey>";
										$x_machine_stations.= "<name><![CDATA[".$m['readername']."]]></name>";
										$x_machine_stations.= "</reader>";

									}



									$x_machine_stations.= "</readers>";

								/*
									ALLE RECORDS RAUW UIT DE DB VOOR DEZE MACHINE
								*/


								$sqlqstr="SELECT * FROM work_planner_logboek where machinekey='".trim($MACHINE_KEY)."' order by id desc";	 

								$x_machine_log_raw = "<raw>";

								foreach($mysql_connectie->query($sqlqstr) as $m) {

										$CHECKIN_TIME = "";
										$CHECKOUT_TIME = "";
										$MINUTES = "0";
										$STATION_NAME = "Unknown";

										if(trim($m['checkin']) != ""){
											$CHECKIN_TIME = date("H:i",strtotime($m['checkin']));
										}
										if(trim($m['checkout']) != ""){
											$CHECKOUT_TIME = date("H:i",strtotime($m['checkout']));
											$start_date = new DateTime($m['checkin']);
											$since_start = $start_date->diff(new DateTime($m['checkout']));
											$MINUTES = $since_start->i;
											$MINUTES += $since_start->h * 60;
										} else {
											$CHECKOUT_TIME = "";
										}

										$TARIEF = "0";
										$sql_= "SELECT * FROM work_planner_readers where id='".trim($m['userid'])."' LIMIT 1";	
										foreach($mysql_connectie->query($sql_) as $r) {
											$STATION_NAME = ucwords(strtolower($r['readername']));
											$USERID_ = $r['id'];
											$TARIEF = trim($r['tarief']);
											break;
										}


										if((int)$MINUTES > 1 ){

										$x_machine_log_raw.= "<entry>";
											$x_machine_log_raw.= "<id><![CDATA[".$m['id']."]]></id>";
											$x_machine_log_raw.= "<d><![CDATA[".$m['dag']."]]></d>";
											$x_machine_log_raw.= "<userid><![CDATA[".$USERID_."]]></userid>";
											$x_machine_log_raw.= "<station><![CDATA[".trim($STATION_NAME)."]]></station>";
											$x_machine_log_raw.= "<stationkey><![CDATA[".$m['readerkey']."]]></stationkey>";
											$x_machine_log_raw.= "<checkin><![CDATA[".$CHECKIN_TIME."]]></checkin>";
											$x_machine_log_raw.= "<checkout><![CDATA[".$CHECKOUT_TIME."]]></checkout>";
											$x_machine_log_raw.= "<minutes><![CDATA[".$MINUTES."]]></minutes>";
										$x_machine_log_raw.= "</entry>";		


										}




								}

								$x_machine_log_raw.= "</raw>";



								$antwoord = "<xml>";
									$antwoord.=$x_machine_info.$x_html_table;
									$antwoord.=$x_machine_stations;
									$antwoord.=$x_machine_log_raw;							
								$antwoord.="</xml>";


								$OUTPUT_VAR=$antwoord;
							break;
							case "get-data-workplanner-single-user":

								$userkey = $_GET['id'];
								$SELECTED_MONTH = trim($_GET['m']);
								$SELECTED_YEAR = trim($_GET['y']);



								$antwoord = "<div class=\"panel panel-default\">";

										$STATION_NAME=$userkey ;
										$sql_= "SELECT * FROM work_planner_readers where id='".$userkey."' LIMIT 1";	
										foreach($mysql_connectie->query($sql_) as $r) {
											$STATION_NAME = ucwords(strtolower($r['readername']));
											break;
										}



								$TITLE_= "Urenoverzicht van <b>".$STATION_NAME."</b> - <a href=\"/?action=workshop-machine-overview\" title=\"Overzicht en informatie van aangemelde machines\">Bekijk overzicht van machines</a>";

								$antwoord.= "<div class=\"panel-heading\">".$TITLE_."</div>";


								$antwoord.= "<div class=\"panel-body\" style=\"background:#FCFCFC;\">";
 
 								// -------------------------------------- TABS JAARGANGEN
 								$begin_jaar = 2018;
 								$cur_jaar = date("Y");
 								$TAB_YEARS_HTML = "<div class=\"btn-group\">";
 								$TAB_YEARS_HTML.= "<button type=\"button\" class=\"btn btn-default dropdown-toggle\" ";
 								$TAB_YEARS_HTML.= " data-toggle=\"dropdown\" aria-haspopup=\"true\" aria-expanded=\"false\">";
 								$TAB_YEARS_HTML.= $SELECTED_YEAR."&nbsp;<span class=\"caret\"></span>";
 								$TAB_YEARS_HTML.= "</button><ul class=\"dropdown-menu\">";

								for($i=$begin_jaar;$i<(int)$cur_jaar+1;$i++){
									$cl="";

									$URL_ = "/?action=workshop-see-employee&u=".$userkey."&year=".trim($i)."&m=".$SELECTED_MONTH;
									if(trim($SELECTED_YEAR)==trim($i)){$cl=" class=\"active\" ";}
								 	$TAB_YEARS_HTML.= "<li ".$cl."><a href=\"".$URL_."\">".($i)."</a></li>";
								}
								$TAB_YEARS_HTML.= "</ul></div>";


 





 								// -------------------------------------- TABS MAANDEN

								$ma = Array("Januari","Februari","Maart","April","Mei","Juni","Juli","Augustus","September","Oktober","November","December");
 								$TAB_MONTHS_HTML = "<div class=\"btn-group\">";
 								$TAB_MONTHS_HTML.= "<button type=\"button\" class=\"btn btn-primary dropdown-toggle\" ";
 								$TAB_MONTHS_HTML.= " data-toggle=\"dropdown\" aria-haspopup=\"true\" aria-expanded=\"false\">";
 								$TAB_MONTHS_HTML.= $ma[(int)$SELECTED_MONTH-1]."&nbsp;<span class=\"caret\"></span>";
 								$TAB_MONTHS_HTML.= "</button><ul class=\"dropdown-menu\">";
								for($i=0;$i<count($ma);$i++){
									$URL_ = "/?action=workshop-see-employee&u=".$userkey."&year=".$SELECTED_YEAR."&m=".((int)$i+1);
									$cl="";
									if((int)$SELECTED_MONTH==((int)$i+1)){$cl=" class=\"active\" ";}
								 	$TAB_MONTHS_HTML.= "<li  ".$cl."><a href=\"".$URL_."\">".$ma[$i]."</a></li>";
								}
								$TAB_MONTHS_HTML.= "</ul></div>";



								$antwoord.= "<div class=\"row\"><div class=\"col-md-1\">Kies een periode:</div><div class=\"col-md-1\">".$TAB_YEARS_HTML."</div><div class=\"col-md-1\">".$TAB_MONTHS_HTML."</div></div>";




								

								$datum1 = $SELECTED_YEAR."/".$SELECTED_MONTH."/1";
								$last_day___ = cal_days_in_month(CAL_GREGORIAN, $SELECTED_MONTH, $SELECTED_YEAR);
								$datum2 = $SELECTED_YEAR."/".$SELECTED_MONTH."/".$last_day___;




										$teller=0;
										$ROOSTER_ = "";
										$sql= "SELECT * FROM work_planner_logboek where userid='".$userkey."' AND checkin BETWEEN '".$datum1."' AND '".$datum2."' group by dag order by dag desc";	
										foreach($mysql_connectie->query($sql) as $r) {
											 
 											
												$ROOSTER_.= "<div class=\"row\"><div class=\"col-md-2 text-right\">";

													$ROOSTER_.= "<b>".$r['dag']."</b>";	



												$ROOSTER_.= "</div><div class=\"col-md-6\">";


														// Checkins overzicht 
														$sql0= "SELECT * FROM work_planner_logboek where userid='".$userkey."' and dag='".$r['dag']."'";

														foreach($mysql_connectie->query($sql0) as $t) {


																	$MACHINENAAM = $t['machinekey'];
																	$sql1_= "SELECT * FROM work_planner_machines where id='".$t['machinekey']."' LIMIT 1";	
																	foreach($mysql_connectie->query($sql1_) as $u) {
																		$MACHINENAAM = ucwords(strtolower($u['makemodel']));
																		break;
																	}

																if(trim($t['checkout']) !=""){	
																$regel = "<p>".date("H:i",strtotime($t['checkin']))." - ".date("H:i",strtotime($t['checkout']))." : ".$MACHINENAAM."</p>";		
												 				$ROOSTER_.= $regel;	
												 				}


														}


												$ROOSTER_.= "</div></div>";	


												$teller=$teller+1;
										}
										


										if($teller==0){
											$DAGEN_ = "<center>Niets gevonden! Selecteer een andere periode.</center>";									
										}else {
											$DAGEN_ = $ROOSTER_;
										}		



								$antwoord.="</div><div class=\"panel-body\">".$DAGEN_."</div></div>";
								$OUTPUT_VAR=$antwoord;

							break;
							case "save-unload-form":

							 
								$id= trim($_GET['id']);
								$xml_=trim($_POST['sync_data']);

								$sql = "UPDATE commercial_data SET ";
									$sql.= "unload_ref_xml='".trim($xml_)."' ";
									$sql.= "WHERE id='".$id."'  ";
							

								

								$ok = 	$mysql_connectie->query($sql);

					 
								$OUTPUT_VAR = $ok;

							break;
							case "set-workplanner-machine-archive":

								$val= trim($_GET['archivevalue']);
								$id= trim($_GET['id']);


								$sql = "UPDATE work_planner_machines SET ";
									$sql.= "archief='".trim($val)."' ";
									$sql.= "WHERE id='".$id."'  ";
							

								

								$ok = 	$mysql_connectie->query($sql);

					 
								$OUTPUT_VAR = $ok;

							break;
							case "get-rows-workplanner-machines":

								$antwoord = "";
								if(isset($_GET['archief']) && trim($_GET['archief'])=="1"){
									$add_ = " WHERE archief='1' ";
								}else {
									$add_ = " WHERE archief='0' ";
								}
								$sqlqstr="SELECT * FROM work_planner_machines ".$add_." order by ontop desc,makemodel ";



								if(isset($_GET['getbarcode-list'])){

										foreach($mysql_connectie->query($sqlqstr) as $wpm) {	
											//	$bcode = create_barcode();

												if(trim($antwoord)==""){
													$antwoord= trim($wpm['id']);	
												} else {
													$antwoord.= "|".trim($wpm['id']);
												}
										}
								} else {
										foreach($mysql_connectie->query($sqlqstr) as $wpm) {	
											//	$bcode = create_barcode();
												$link = "/?action=workshop-machine-overview&m=".$wpm['id'];

												$IS_IN_ARCHIEF = trim($wpm['archief']);

												$optie1_link = "/?action=create-work-order-for&machine=".$wpm['id'];
												$opties = "<a href=\"".$optie1_link."\" title=\"Werkorder maken\" class=\"btn btn-sm btn-default\"><span class=\"fa fa-clipboard\"></span> Nieuwe taak</a>&nbsp;";
												$menu_ =$opties;

												$menu_ = "";	
												if($IS_IN_ARCHIEF=="1"){
													$Qr = 0;
													$txt_btn = "Uit archief halen en terug bij de actuele machines";
												} else {
													$txt_btn = "Naar archief verplaatsen";
													$Qr = 1;
												}

												$optie1_link = "/?action=workshop-machine-overview&toarchive=".$Qr."&id=".$wpm['id'];
												$opties = "<a href=\"".$optie1_link."\"  class=\"btn btn-sm btn-default\" title=\"".$txt_btn."\"><span class=\"fa fa-archive\"></span></a>";
												$menu_.= $opties;



												if(trim($wpm['ontop']) == "1"){
												  $menu_ = "";	
												}









												$TAAK_ID = $wpm['id'];
												$DAG_ = date("d-m-Y");

												// Wie is/zijn er nu ingechecked op deze taak?--------------------------

													$sql1="SELECT * FROM work_planner_logboek where dag='".$DAG_."' and machinekey='".$TAAK_ID."' and checkout IS NULL";	

													$WAARDE_ = "";

													foreach($mysql_connectie->query($sql1) as $p) {	

														$nm="";
														$iswasser=0;

														$sql2="SELECT * FROM work_planner_readers where id='".$p['userid']."' LIMIT 1";
														foreach($mysql_connectie->query($sql2) as $b) {	
															// haal naam op
															$nm = $b['readername'];
															$iswasser = trim($b['readername']);
														}

 
														$CHECKIN = date("H:i",strtotime($p['checkin']));


														$NAAM_LINK = "<a href='/?action=workshop-see-employee&u=".trim($p['userid'])."' title=\"Bekijk meer van ".$nm."\">".$nm."</a>";
														$NAAM_LINK.= "";


														$VELD = $NAAM_LINK." (".$CHECKIN.")";

														if(trim($WAARDE_) == "" ){
															$WAARDE_ = $VELD;
														} else {
															$WAARDE_.=", ".$VELD;
														}


														


													//	$start_date = new DateTime($m['checkin']);
													//	$since_start = $start_date->diff(new DateTime($m['checkout']));
													//	$MINUTES = $since_start->i;






													}

												// ----------------------------------------------------------------------		

												if(trim($WAARDE_)==""){$WAARDE_="";}	

												$antwoord.= "<tr><td><a href=\"".$link."\" title='Klik hier voor het overzicht van de uren'>".$wpm['makemodel']."</a>";


												// data 

												$id1 = $wpm['id'];


												$sql1="SELECT * FROM work_planner_logboek where machinekey='".$TAAK_ID."' order by id desc LIMIT 1";	

												//$WAARDE_ = "";
												$LAST_WORKED_DAY =  "";	
												foreach($mysql_connectie->query($sql1) as $p) {	

														$LAST_WORKED_DAY = $p["dag"];


												}



												// -------------- WAS UREN --------------- //	
												$GEWENSTE_WASUREN_ = 0;
												$HUIDIG_AANTAL_WASUREN_ = 0;






												if(trim($wpm['ontop']) != "1"){
														$LASTUPDATE = $LAST_WORKED_DAY." ".date("d-m-Y");

														if(trim($LAST_WORKED_DAY)==trim(date("d-m-Y"))){
															$LASTUPDATE = "Vandaag";	
														}else {
																$LASTUPDATE = $LAST_WORKED_DAY;

														}


														$antwoord.="<br />".$LASTUPDATE;

												}

												$antwoord.= "</td>";

												//if($IS_IN_ARCHIEF != "1"){
													$antwoord.= "<td>".$WAARDE_."</td>";
											//	}
												$antwoord.= "<td style='width:5%'>".$menu_."</td>";
												$antwoord.= "</tr>";
										}

								}
								$OUTPUT_VAR=$antwoord;	




							break;							
							case "get-forgotten-checkouts":
													$DAG = date("d-m-Y");
													$sql1="SELECT * FROM work_planner_logboek where dag <> '".$DAG."' and checkout IS NULL";
													 
													$teller = 0;
													$mxml = "<xml><wrapper>";
													
													foreach($mysql_connectie->query($sql1) as $p) {	

															$MAKE_ = $p['machinekey'];
															$sql_p="SELECT * FROM work_planner_machines where id = '".$MAKE_."' LIMIT 1";
															foreach($mysql_connectie->query($sql_p) as $v) {	
																$MAKE_ = $v['makemodel'];
																break;
															}

															$USER_ = $p['userid'];
															$sql_p="SELECT * FROM work_planner_readers where id = '".$USER_."' LIMIT 1";
															foreach($mysql_connectie->query($sql_p) as $v) {	
																$USER_ = $v['readername'];
																break;
															}

															
															$mxml.= "<machine><logid><![CDATA[".$p['id']."]]></logid><m><![CDATA[".$MAKE_."]]></m>";
															
															$mxml.= "<user><![CDATA[".$USER_."]]></user>";
															$mxml.= "<dag><![CDATA[".$p['dag']."]]></dag>";
															$mxml.= "<checkin><![CDATA[".$p['checkin']."]]></checkin></machine>";

													}



													$mxml.= "</wrapper></xml>";
													

													$OUTPUT_VAR=$mxml;

													 



							break;					
							case "force-checkout-on-logid":

									$LOGID = trim($_GET['id']);
									$STAMP = urldecode(trim($_GET['stamp']));


									$sql = "UPDATE work_planner_logboek SET ";
									$sql.= "checkout='".$STAMP."' ";
									$sql.= "WHERE id='".$LOGID."'  ";

								if ($mysql_connectie->query($sql)) {
											 
								    			$OUTPUT_VAR="OK";	
										} else {
												$OUTPUT_VAR="FAIL| ".$mysql_connectie->errorInfo()[2];	
								  }


							break;
							case "get-users-checkedin":

									$TOTAAL_TELLER = 0;$INCHECK_TELLER=0;
									$sql1="SELECT * FROM work_planner_readers where offline != '1' and hide_me != '1' order by readername";	
									$TIP = "";
									foreach($mysql_connectie->query($sql1) as $p) {	

										$EMPLOYEE_ID = $p['id'];
										$EMPLOYEE_NAME = $p['readername'];
										$EMPL_CHECKIN = "X";
										$TOTAAL_TELLER = $TOTAAL_TELLER + 1;

										$DAG = date("d-m-Y");
										$sql1="SELECT * FROM work_planner_logboek where  dag='".$DAG."' and  userid='".$EMPLOYEE_ID."' and checkout IS NULL";	


										foreach($mysql_connectie->query($sql1) as $p) {	
											$EMPL_CHECKIN = "V";
											$INCHECK_TELLER = $INCHECK_TELLER + 1;
										}

										$TIP.= $EMPL_CHECKIN." ".$EMPLOYEE_NAME."\n";


									}




									$OUTPUT_VAR= "<span><a href=\"/?action=workshop-machine-overview\" title=\"".$TIP."\">".$INCHECK_TELLER."/".$TOTAAL_TELLER."</a></span>";	


							break;
							case "get-machines-for-dropdown-btn":
													$sql1="SELECT * FROM work_planner_machines where archief <> '1' order by makemodel";	

													$WAARDE_ = "";
														
													$mxml = "<xml><wrapper>";
													


													foreach($mysql_connectie->query($sql1) as $p) {	


														$ID_ = $p['id'];
														$MAKE_ = strtoupper($p['makemodel']);

														if(trim($p['ontop']) != "1"){
															$mxml.= "<machine><id><![CDATA[".$ID_."]]></id><m><![CDATA[".$MAKE_."]]></m></machine>";
														}

														


													}
													$mxml.= "</wrapper></xml>";
													

												$OUTPUT_VAR=$mxml;

							break;
							case "get-data-commcercialinvoice-xml":

								$id= trim($_GET['id']);


								$antwoord = "";


								$sqlqstr="SELECT * FROM commercial_data where id = '".$id."' LIMIT 1";
								foreach($mysql_connectie->query($sqlqstr) as $pi) {	

									$antwoord = "<xml><wrapper>";

										$antwoord.= "<info>";

											$antwoord.= "<destination><![CDATA[".$pi['tos_descr']."]]></destination>";
											$antwoord.= "<invoiceno><![CDATA[".trim($pi['ci_no'])."]]></invoiceno>";



										$antwoord.= "</info>";


										$antwoord.= $pi['buyer_xml'];
										$antwoord.= $pi['goods_xml'];




									$antwoord.="</wrapper></xml>";	




									break; 		
								}





								$OUTPUT_VAR = $antwoord;



							break;
							case "get-unload-ref-xml":

								$antwoord = "EOF";
								$id= trim($_GET['id']);
								$sqlqstr="SELECT * FROM commercial_data where id = '".$id."' LIMIT 1";
								foreach($mysql_connectie->query($sqlqstr) as $pi) {	
									if(trim($pi['unload_ref_xml']) != ""){
										$antwoord = $pi['unload_ref_xml'];
									}
									 	
								}


								$OUTPUT_VAR = $antwoord;

							break;
							case "save-machine-for-workplanner":
								$machine_xml =  simplexml_load_string($_POST['sync_data']);
								 
								$mkey = $machine_xml->key;
								$mmodel = $machine_xml->n;
								$mserial = $machine_xml->no;
								$myear = $machine_xml->j;
								$est_ = $machine_xml->est;

					 
								$sql = "INSERT INTO work_planner_machines (stamp,machinekey,makemodel,serial,year,klaar,estimate_hours) VALUES (now(),'".$mkey."','".$mmodel."','".$mserial."','".$myear."','0','".$est_."')"; 


								if ($mysql_connectie->query($sql)) {
								    			$OUTPUT_VAR="OK";	
										} else {
												$OUTPUT_VAR="FAIL| ".$mysql_connectie->errorInfo()[2];	
								  }



							break;
							case "save-terug-bel-afspraak":
								$bel_xml =  trim($_POST['sync_data']);
								 
						
								$tijd = date("h:i");
								$FOUND = 0;
								$sql = "INSERT INTO callbacks (datum,tijd,bel_xml,klaar) VALUES (now(),'".$tijd."','".trim($bel_xml)."','0')"; 


								if ($mysql_connectie->query($sql)) {
								    			$OUTPUT_VAR="OK";	
										} else {
												$OUTPUT_VAR="FAIL| ".$mysql_connectie->errorInfo()[2];	
								  }


							break;
							case "save-kosten-data":
								$kosten_xml =  trim($_POST['sync_data']);
								$stamp = trim($_GET['q']);
						

								$FOUND = 0;
								$sqlqstr="SELECT * FROM KOSTEN where maand='".$stamp."' LIMIT 1";
								foreach($mysql_connectie->query($sqlqstr) as $pi) {	
									$FOUND = 1;
								}


								if($FOUND == 0){

									// NEW RECORD
									$sql = "INSERT INTO kosten (maand,kostendata) VALUES ('".$stamp."','".trim($kosten_xml)."')"; 
								} else {


									// EDIT RECORD
									$sql = "UPDATE kosten SET ";
									$sql.= "kostendata='".trim($kosten_xml)."' ";
									$sql.= "WHERE maand='".$stamp."'  ";
								}

								



								if ($mysql_connectie->query($sql)) {
												$last_id = $stamp;
								    			$OUTPUT_VAR=$last_id;	
										} else {
												$OUTPUT_VAR="FAIL| ".$mysql_connectie->errorInfo()[2];	
								  }


							break;
							case "save-invoice-data":




									//$x = "<xml><no><![CDATA[219]]></no><date><![CDATA[26-07-17]]></date><buyer><naambv><![CDATA[Euro Auctions LTD]]></naambv><street><![CDATA[Roall Ln]]></street><postal><![CDATA[Nr Ferrybridge DN14 0ny]]></postal><city><![CDATA[Leeds]]></city><regio><![CDATA[]]></regio><country><![CDATA[United Kingdom]]></country><vat><![CDATA[791496386]]></vat><id><![CDATA[undefined]]></id></buyer><goods><equipment><title><![CDATA[USED BULLDOZER]]></title><qty><![CDATA[1]]></qty><price><![CDATA[37500]]></price><specification><spec><name><![CDATA[make]]></name><value><![CDATA[CATERPILLAR]]></value></spec><spec><name><![CDATA[model]]></name><value><![CDATA[D6H]]></value></spec><spec><name><![CDATA[year]]></name><value><![CDATA[1989]]></value></spec><spec><name><![CDATA[weight]]></name><value><![CDATA[22500]]></value></spec><spec><name><![CDATA[hours]]></name><value><![CDATA[12499]]></value></spec></specification></equipment><equipment><title><![CDATA[USED BULLDOZER]]></title><qty><![CDATA[1]]></qty><price><![CDATA[67500]]></price><specification><spec><name><![CDATA[make]]></name><value><![CDATA[CATERPILLAR]]></value></spec><spec><name><![CDATA[model]]></name><value><![CDATA[D7H]]></value></spec><spec><name><![CDATA[year]]></name><value><![CDATA[1994]]></value></spec><spec><name><![CDATA[weight]]></name><value><![CDATA[29800]]></value></spec><spec><name><![CDATA[hours]]></name><value><![CDATA[19700]]></value></spec></specification></equipment></goods></xml>";

									$invoice_xml =  simplexml_load_string($_POST['sync_data']);
			


									$invoice_number =  $invoice_xml->no;
									$invoice_date =  $invoice_xml->date;
									$invoice_vat = $invoice_xml->vat;
									$invoice_tos = $invoice_xml->tos;
									$invoice_tosdescr = $invoice_xml->tosdescr;
									$invoice_currency = $invoice_xml->currency;
									$invoice_downpayment = $invoice_xml->downpay;	
									$invoice_downpayment_within_days = $invoice_xml->downpaydays;
									$invoice_balance_within_days = $invoice_xml->balancedays;		
									$invoice_taal = $invoice_xml->invoicelang;


									$invoice_type = $invoice_xml->invoicetype; // 1 = machine 2 = handmatig

									$invoice_additional_payment_terms = $invoice_xml->addterms;


								 	$buyer_naambv = $invoice_xml->buyer->naambv;
								 	$buyer_street = $invoice_xml->buyer->street;
								 	$buyer_postal = $invoice_xml->buyer->postal;
								 	$buyer_city = $invoice_xml->buyer->city;
								 	$buyer_regio = $invoice_xml->buyer->regio;
								 	$buyer_country = $invoice_xml->buyer->country;
								 	$buyer_vat = $invoice_xml->buyer->vat;


								 	$equipment_xml = $invoice_xml->goods->asXml();
								 	$buyer_xml = $invoice_xml->buyer->asXml();


								 	$type_terms = $invoice_xml->typeterms;
								 	$type_terms_3_add = $invoice_xml->pt3additionaltext;
								 	$dp_money = $invoice_xml->downpaymoney;
								 	$payment_css = $invoice_xml->paymentcss;


								 	$teststring = "No: ".$invoice_number;
								 	$teststring.= "<br />Invoice date: ".$invoice_date;
								 	$teststring.= "<br />Buyer: ";
								 	$teststring.= "<br />.... ".$buyer_naambv;
								 	$teststring.= "<br />.... ".$buyer_street;
								 	$teststring.= "<br />.... ".$buyer_postal;
								 	$teststring.= "<br />.... ".$buyer_city;
								 	$teststring.= "<br />.... ".$buyer_regio;
								 	$teststring.= "<br />.... ".$buyer_country;
								 	$teststring.= "<br />.... ".$buyer_vat;

								 //	$OUTPUT_VAR = $teststring;


								 	if(isset($_GET['edit'])){
								 		$EDIT_PROFORMA = trim($_GET['edit']);
								 	} else {
								 		$EDIT_PROFORMA = "false";
								 	}


								 	if(strtolower($EDIT_PROFORMA)=="false"){

								 		// NEW PROFORMA, ADD NEW RECORD IN DB
										$sql = "INSERT INTO proforma_data (invoice_date,geplaatst,invoice_no,invoice_buyer_name,invoice_buyer_country,buyer_xml,goods_xml,vat,tos,tos_descr,currency,downpaymentpercentage,downpaydays,balancedays,invoice_type,taal,additional_payment_term,typeterms,typeterms_add_3,downpayment_money,payment_css) ";
										$sql.= " VALUES ('".$invoice_date."',now(),'".$invoice_number."','".$buyer_naambv."','".$buyer_country."','".$buyer_xml."','".$equipment_xml."','".$invoice_vat."','".$invoice_tos."','".$invoice_tosdescr."','".$invoice_currency."','".$invoice_downpayment."','".$invoice_downpayment_within_days."','".$invoice_balance_within_days."','".$invoice_type."','".$invoice_taal."','".$invoice_additional_payment_terms."','".$type_terms."','".$type_terms_3_add."','".$dp_money."','".$payment_css."')"; 
								 	} else {


								 		// PROFORMA IS BEEING EDITED, SAVE TO THE ORIGINAL RECORD
 
								 		$sql = "UPDATE proforma_data SET ";

								 		$sql.= "invoice_date='".$invoice_date."',";
								 		$sql.= "laatste_aanpassing=now(),";

								 		$sql.= "invoice_no='".$invoice_number."',";
								 		$sql.= "invoice_buyer_name='".$buyer_naambv."',";
								 		$sql.= "invoice_buyer_country='".$buyer_country."',";
								 		$sql.= "buyer_xml='".$buyer_xml."',";

								 		$sql.= "goods_xml='".$equipment_xml."',";
								 		$sql.= "vat='".$invoice_vat."',";
								 		$sql.= "tos='".$invoice_tos."',";
								 		$sql.= "tos_descr='".$invoice_tosdescr."',";
								 		$sql.= "currency='".$invoice_currency."',";
								 		$sql.= "downpaymentpercentage='".$invoice_downpayment."',";
								 		$sql.= "downpaydays='".$invoice_downpayment_within_days."',";
								 		$sql.= "invoice_type='".$invoice_balance_within_days."',";	
								 		$sql.= "taal='".$invoice_taal."',";
								 		$sql.= "payment_css='".trim($payment_css)."',";
								 		$sql.= "typeterms='".$type_terms."',";
								 		$sql.= "typeterms_add_3='".$type_terms_3_add."',";
								 		$sql.= "downpayment_money='".$dp_money."',";
								 		$sql.= "additional_payment_term='".trim($invoice_additional_payment_terms)."',";
								 		$sql.= "invoice_type='".$invoice_type."' ";

								  		$sql.= "WHERE id='".$EDIT_PROFORMA."' ";	


								 	}

 

									if ($mysql_connectie->query($sql)) {
												$last_id = $mysql_connectie->lastInsertId();
								    			$OUTPUT_VAR=$last_id;	
										} else {
												$OUTPUT_VAR="FAIL| ".$mysql_connectie->errorInfo()[2];	
								  }



							break;
							case "get-equipment-data":	
							


									if(isset($_GET['id'])){

										$OUTPUT_VAR = "EOF";
										$id= trim($_GET['id']);

										$sqlqstr="SELECT * FROM machines where machineid='".$id."'";

													foreach($mysql_connectie->query($sqlqstr) as $saveddata) {
 
														$current_make = $saveddata['make'];
														$current_model = $saveddata['model'];
														$current_year = $saveddata['year'];
														$current_weight = $saveddata['weight'];
													 	$current_listing = $saveddata['listed'];
														$current_category =  $saveddata['category'];
														$current_hours = $saveddata['hours'];

														$current_weight = trim(str_replace("KG", "", strtoupper($current_weight)));
														$current_listing = trim(urldecode(str_replace("%E2%82%AC", "",  urlencode($saveddata['listed']) )));

														if (strpos($current_hours, 'NEW') !== false) {
														    $pre_title = "UNUSED";
														}else {
															$pre_title= "USED";
														}

														$title = $pre_title." ".strtoupper($current_category);
														
														$eqxml = "<xml>";
														$eqxml.= "<id><![CDATA[".$id."]]></id>";	

														$eqxml.= "<title><![CDATA[".$title."]]></title>";	

														if(trim($current_make) !="")	
														$eqxml.= "<make><![CDATA[".$current_make."]]></make>";

														if(trim($current_model) !="")
														$eqxml.= "<model><![CDATA[".$current_model."]]></model>";	

														if(trim($current_year) !="")
														$eqxml.= "<year><![CDATA[".$current_year."]]></year>";	

														if(trim($current_weight) !="")
														$eqxml.= "<weight><![CDATA[".$current_weight."]]></weight>";	

														if(trim($current_hours) !="")
														$eqxml.= "<hours><![CDATA[".$current_hours."]]></hours>";	

														if(trim($current_listing) !="")
														$eqxml.= "<listed><![CDATA[".$current_listing."]]></listed>";	

														$eqxml.= "</xml>";
														$OUTPUT_VAR= ($eqxml);
														break;
													}



									} else {



										$OUTPUT_VAR = "EOF";



									}






							break;
							case "relation_details":

								$OUTPUT_VAR = "<xml></xml>";


								if(isset($_GET['id']) && trim($_GET['id']) != ""){

									$sqlqstr="SELECT * FROM relaties where id='".trim($_GET['id'])."'";


										foreach($mysql_connectie->query($sqlqstr) as $bv) {

												$OUTPUT_VAR = "<xml>";


												$OUTPUT_VAR.= "<naambv><![CDATA[".trim($bv['naambv'])."]]></naambv>";
												$OUTPUT_VAR.= "<street><![CDATA[".trim($bv['street'])."]]></street>";

												if(trim($bv['postal'])=="0"){
													$OUTPUT_VAR.= "<postal></postal>";
												} else{
													$OUTPUT_VAR.= "<postal><![CDATA[".trim($bv['postal'])."]]></postal>";
												}
												
												$OUTPUT_VAR.= "<city><![CDATA[".trim($bv['stad'])."]]></city>";
												$OUTPUT_VAR.= "<region><![CDATA[".trim($bv['regio'])."]]></region>";
												$OUTPUT_VAR.= "<country><![CDATA[".trim($bv['land'])."]]></country>";		

												if(trim($bv['regno'])=="0"){
													$OUTPUT_VAR.= "<vat></vat>";
												} else{
													$OUTPUT_VAR.= "<vat><![CDATA[".trim($bv['regno'])."]]></vat>";
												}


												

												// adres (str + #)

												// postal


												


												$OUTPUT_VAR.= "</xml>";
										 
												break;
										}


								}



								 


							break;
							case "save-verlof":

									$t = "<xml><werknemer><![CDATA[frans]]></werknemer><reden><![CDATA[Dokter/Tandarts/Ziekenhuis]]></reden><van><![CDATA[02/01/2018]]></van><tot><![CDATA[02/17/2018]]></tot><om><![CDATA[Geen omschrijving bitch]]></om></xml>";

									$XMLVERLOF = simplexml_load_string($t);

									if(isset($_POST['sync_data'])){
											$XMLVERLOF = simplexml_load_string($_POST['sync_data']);
									}


									$naam = $XMLVERLOF->werknemer;
									$reden = $XMLVERLOF->reden;
									$van=$XMLVERLOF->van;
									$tot=$XMLVERLOF->tot;
									$om=$XMLVERLOF->om;

									$p= explode("/", $van);
									$van1 = $p[2]."-".$p[0]."-".$p[1];

									if(trim($tot) != ""){
											$p= explode("/", $tot);
											$tot1 = $p[2]."-".$p[0]."-".$p[1];								
									} else {
										$tot1 = $van1;
									}



									$sql_art = "INSERT INTO verlof (wie, wat, van, tot,omschrijving) ";
									$sql_art.= " VALUES ('".$naam."', '".$reden."', '".$van1."', '".$tot1."', '".$om."')";


									if ($mysql_connectie->query($sql_art)) {
	
											$OUTPUT_VAR = "OK";			
									} else {
											$OUTPUT_VAR="FAIL|".$mysql_connectie->errorInfo()[2];				

									}


							break;
							case "save_sync":


								
									if(isset($_POST['sync_data'])){
											$xml = simplexml_load_string($_POST['sync_data']);
									}
									

									$teller = 0;
									$toegevoegd = 0;
									$bijgewerkt = 0;
									$error1="";

									// clear database

									$sqlqstr="DELETE FROM machines";
									$del = $mysql_connectie->prepare($sqlqstr);
									$del->execute();

											$dir = "img/";
											$di = new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS);
											$ri = new RecursiveIteratorIterator($di, RecursiveIteratorIterator::CHILD_FIRST);
											foreach ( $ri as $file ) {
											    $file->isDir() ?  rmdir($file) : unlink($file);
											}



									$volgnummer = 0;

									foreach($xml->machine as $machine)
									{
											$volgnummer = $volgnummer + 1;

											$id= $machine->id;
											$make= ($machine->brand);
											$model= ($machine->model);
											$year=$machine->year;
											$weight=$machine->weight;
											$img = $machine->img;
											$price=$machine->price;
											$category=$machine->category;
											$hours=$machine->hours;
											$profile_url=urlencode($machine->url);
											$youtube=$machine->youtube;	


											/* MACHINES OPSLAAN IN ARCHIEF DATABASE //////////////////////////////
 											

												deze module kijkt of we de betreffende machien in onze archief database hebben. 
												Zo niet dan slaan we deze er in op		


											*/
													$db_make = strtoupper(trim($make));
													$db_model = strtoupper(trim($model));
													$db_year = trim($year);
													$db_weight = strtoupper($weight);
													//$db_serial = trim($serial);
													$db_hours = trim($hours);

													$sql_str = "SELECT * from machine_db where make='".trim($db_make)."' AND model='".trim($db_model)."' and year='".trim($db_year)."'";

													$del = $mysql_connectie->prepare($sql_str);
													$del->execute();
													$c=$del->rowCount();
													unset($del);

													if($c == 0) {
																//machine bestaat niet
												 			//if(trim($db_year) != ""){
												 				/* 
																	Moet mininaal een bouwjaar hebben. 

																*/
																$sql_art = "INSERT INTO machine_db (make, model, year, weight,added,category) ";
																$sql_art.= " VALUES ('".$db_make."', '".$db_model."', '".$db_year."', '".$db_weight."', now(), '".trim(strtolower($category))."')";
																$mysql_connectie->query($sql_art);
																unset($sql_art);
												 			//}											

													}
											

											///////////////////////////////////////////////////////////////////////////










												$sqlqstr="SELECT * FROM machines where machineid='".$id."'";

												$del = $mysql_connectie->prepare($sqlqstr);
												$del->execute();
												$c=$del->rowCount();


											if($c == 0){

												// BESTAAT NIET, MAAK INSERT

												$SQL_JEC = "INSERT INTO machines ";
												$SQL_JEC.= "(";
												$SQL_JEC.= "volgnummer,make,model,year,weight,machineid,image,category,hours,url_to_website,youtubevideo";

												if(trim($price) != ""){
														$SQL_JEC.= ",listed";
												}

										 		$SQL_JEC.= ") VALUES (";
												$SQL_JEC.= " '".$volgnummer."','".$make."','".$model."','".$year."','".$weight."', '".$id."', '".$img."', '".$category."', '".$hours."','".$profile_url."','".$youtube."' ";

												if(trim($price) != ""){
														$SQL_JEC.= ", '".$price."'";
												}

												$SQL_JEC.= ")";

												if(trim($img)==""){
													$img = "dummy.jpg";
												}else {
													download_img($img,$id);
												}

															if ($mysql_connectie->query($SQL_JEC)) {
									    								
																$toegevoegd = $toegevoegd + 1;
															} else {
																$error1=$mysql_connectie->errorInfo()[2];				

									    					}

											}	else{

 													$value_updated = 0;
													// BESTAAT WEL, KIJKEN WELKE INFO BIJGEWERKT MOET WORDEN

													foreach($mysql_connectie->query($sqlqstr) as $saveddata) {
 
														$current_make = $saveddata['make'];
														$current_model = $saveddata['model'];
														$current_year = $saveddata['year'];
														$current_weight = $saveddata['weight'];
														$current_img = $saveddata['image'];
														$current_listing = $saveddata['listed'];
														$current_category =  $saveddata['category'];
														$current_hours = $saveddata['hours'];
														break;
													}

												


													$SQL_JEC = "UPDATE machines ";
													$SQL_JEC.= "SET";

													$SQL_JEC.= " make='".$make."'";

													if(trim($current_model) != trim($model) && trim($model) != ""){
														$SQL_JEC.= ", model='".$model."'";
														$value_updated = 1;
													}
													
													if(trim($current_year) != trim($year) && trim($year) != ""){
														$SQL_JEC.= ", year='".$year."'";
														$value_updated = 1;
													}	

													if(trim($current_weight) != trim($weight) && trim($weight) != ""){
														$SQL_JEC.= ", weight='".$weight."'";
														$value_updated = 1;
													}															

				 									if(trim($current_category) != trim($category) && trim($category) != ""){
														$SQL_JEC.= ", category='".$category."'";
														$value_updated = 1;
													}		

													if(trim($current_listing) != trim($price) && trim($price) != ""){
														$SQL_JEC.= ", listed='".$price."'";
														$value_updated = 1;
													}

													if(trim($current_img) != trim($img) && trim($img) != ""){
														$SQL_JEC.= ", image='".$img."'";
														download_img($img,$id);
														$value_updated = 1;
													}	


													if(trim($current_hours) != trim($hours) && trim($hours) != ""){
														$SQL_JEC.= ", hours='".$hours."'";
														 
														$value_updated = 1;
													}	


													$SQL_JEC.= " WHERE machineid='".$id."' ";

															if ($mysql_connectie->query($SQL_JEC)) {
									    						if($value_updated==1){
									    							$bijgewerkt = $bijgewerkt + 1;
									    						}		
															} else {
																$error1=$mysql_connectie->errorInfo()[2];				

									    					}


													


											}

 



											 

									}

								 

									if(trim($error1) == "" ){
										// geen error

										$today = date("d-m-Y H:i:s");
										$sqlupdate_waardes = "UPDATE systeemwaardes SET lastsync='".$today."' where id='1'";
										$del = $mysql_connectie->prepare($sqlupdate_waardes);
										$del->execute();
									


										$antwoord = "\nGelukt! ".$toegevoegd." machine(s) toegevoegd en ".$bijgewerkt." bijgewerkt";
									} else {

										$antwoord = "Error: ".$error1;
									}


								$OUTPUT_VAR = $antwoord;




							break;
							case "get_settings":

								$setting = trim($_GET['setting']);

									$sqlqstr="SELECT * FROM settings LIMIT 1";
									foreach($mysql_connectie->query($sqlqstr) as $set) {

											
											if(strtolower($setting)=="theme"){
												$OUTPUT_VAR = $set['theme'];
											}


											break;
									}

							break;
							case "check_vat":

								$vat = trim($_GET['vat']);	


								$count=0;
								$sqlqstr="SELECT * FROM relaties where regno='".$vat."'";

								$del = $mysql_connectie->prepare($sqlqstr);
								$del->execute();
								$c=$del->rowCount();

								if($c > 0) {
									$res=""; // bestaat al

									$sqlqstr="SELECT * FROM relaties where regno='".$vat."'";
									foreach($mysql_connectie->query($sqlqstr) as $bv) {

											$res.="<blockquote><h4>Relatie bestaat al, kies een ander VAT/BTW nummer</h4><p><b>".strtoupper($bv['naambv'])."</b></p>";
											$res.="<p>".($bv['stad'])."</p>";
											$res.="<p>".($bv['land'])."</p></blockquote>";
											break;
									}



								} else {
									$res="OK";
								}


								$OUTPUT_VAR = $res;



							break;
 							case "save_invoice_data":


								$dum =trim($_POST['invoice_data']);

								$xml = simplexml_load_string($dum);

								$datum = $xml->datum;
								$invoice_no = trim($xml->no);
								$buyer= trim($xml->buyer);		
								$equipmentid=trim($xml->equipmentid);	
								$title=trim($xml->title);	
								$price=trim($xml->price);	
								$additional = trim($xml->additional->asXML());	
								$ref=trim($xml->ref);	
								$valuta=trim($xml->valuta);	
								$shipping=trim($xml->shipping);	
								$vat=trim($xml->vat);	
								$shippingto=trim($xml->shippingto);	
								$sql = "INSERT INTO invoice_data (buyerid,machine_id,valuta,price,invoicedate,invoicetype,additional_equipment_xmlstr,termsofsale,payment_reference,vat_precent,volgnummer,title,shippingto) ";
								$sql.= " VALUES ('".$buyer."', '".$equipmentid."', '".$valuta."', '".$price."', '".$datum."', 'PROFORMA','".$additional."' ";
								$sql.= ", '".$shipping."', '".$ref."', '".$vat."', '".$invoice_no."', '".$title."', '".$shippingto."')";


								if ($mysql_connectie->query($sql)) {
							    					$OUTPUT_VAR="OK";	
									} else {
													$OUTPUT_VAR="FAIL| ".$mysql_connectie->errorInfo()[2];	
							    }
			 




							break;
							case "save_relation":

									$naambv = utf8_encode(trim($_GET['naambv']));
									$regno = utf8_encode(trim($_GET['regno']));
									$street = utf8_encode(trim($_GET['straat']));
									$number = utf8_encode(trim($_GET['nummer']));
									$postal = utf8_encode(trim($_GET['postal']));
									$stad = utf8_encode(trim($_GET['city']));
									$regio = utf8_encode(trim($_GET['regio']));
									$land = utf8_encode(trim($_GET['land']));
									$spokepersonname = utf8_encode(trim($_GET['spokename']));
									$spokepersontel = utf8_encode(trim($_GET['spoketel']));
									$spokepersonemail = utf8_encode(trim($_GET['spokemail']));
									$comments = "none";


									$sql = "INSERT INTO relaties (naambv,regno,street,number,postal,stad,regio,land,spokepersonname,spokepersontel,spokepersonemail,comments) VALUES ('".$naambv."', '".$regno."', '".$street."', '".$number."', '".$postal."', '".$stad."','".$regio."', '".$land."', '".$spokepersonname."', '".$spokepersontel."', '".$spokepersonemail."', '".$comments."')";

									if ($mysql_connectie->query($sql)) {
			    								$OUTPUT_VAR = "OK";	
									} else {
												$OUTPUT_VAR = "Error - ".$mysql_connectie->errorInfo()[2];
			    					}

								break;
							case "save_machine":

								

									//make=Liebherr&model=R904HDSL&year=1998&weight=21700&ser=101580501286&enser=00574588
									$make = utf8_encode(trim($_GET['make']));
									$model = utf8_encode(trim($_GET['model']));		
									$year = utf8_encode(trim($_GET['year']));	
									$weight = utf8_encode(trim($_GET['weight']));	
									$ser = utf8_encode(trim($_GET['ser']));	
									$enser = utf8_encode(trim($_GET['enser']));	
									$gepl = date("Y-m-d H:i:s");	
									$country = utf8_encode(trim($_GET['cntry']));



									/*	
									$OUTPUT_VAR = $make."<br />";	
									$OUTPUT_VAR.= $model."<br />";	
									$OUTPUT_VAR.= $year."<br />";	
									$OUTPUT_VAR.= $weight."<br />";
									$OUTPUT_VAR.= $ser."<br />";	
									$OUTPUT_VAR.= $enser."<br />";	
									$OUTPUT_VAR.= $gepl."<br />";	
									*/	

									$sql = "INSERT INTO machines (make, model, serialno, engine_serial,year,weight,country,geplaatst) VALUES ('".$make."', '".$model."', '".$ser."', '".$enser."', '".$year."', '".$weight."','".$country."', '".$gepl."')";

									if ($mysql_connectie->query($sql)) {
			    								$OUTPUT_VAR = "OK";	
									} else {
												$OUTPUT_VAR = "Error - ".$mysql_connectie->errorInfo()[2];
			    					}



							break;
							case "get-all-products-list":
									// 

	


										    $sql_mag = "SELECT * from artikelen order by omschrijving asc";
										
										    $sql_order = "SELECT * from bestellingen group by ordernummer";
											
											$del = $mysql_connectie->prepare($sql_mag);
											$del->execute();
											$c=$del->rowCount();
											unset($del);	
													
											$del = $mysql_connectie->prepare($sql_order);
											$del->execute();
											$o=$del->rowCount();
											unset($del);	
											$sql_order = "SELECT * from bestellingen";
											$del = $mysql_connectie->prepare($sql_order);
											$del->execute();
											$o2=$del->rowCount();
											unset($del);



											$OUTPUT_VAR = "<div class=\"panel-body\">Artikelen: <b>".$c."</b> / Bestellingen: <b>".$o."</b> / Regels: <b>".$o2."</b> - Artikel staat niet in de lijst? <a href=\"/?action=add_product\">Voeg pakbon/bestelling toe</a></div>";

											$OUTPUT_VAR.= "<table class=\"table table-bordered table-hover table-sortable\" id=\"productstbl\">";

											$OUTPUT_VAR.= "<thead><tr><th>Omschrijving<span class=\"fa fa-sort ri\"></span></th><th class=\"text-center\">Eenheid<span class=\"fa fa-sort ri\"></span></th>";
											$OUTPUT_VAR.= "<th class=\"text-right\">Artikelnummer<span class=\"fa fa-sort ri\"></span></th><th>Artikelen / Bestellingen<span class=\"fa fa-sort ri\"></span></th></tr></thead>";
											$OUTPUT_VAR.= "<tbody>";





											foreach($mysql_connectie->query($sql_mag) as $magazijn) {


												$omschrijving =  $magazijn['omschrijving'];
												$artikelnummer =  $magazijn['artikelcode_leverancier'];
												$eenheid =  strtolower($magazijn['eenheid']);

												$OUTPUT_VAR.= "<tr>";

												$OUTPUT_VAR.= "<td>".$omschrijving."</td>";	
												

												$sql_count ="SELECT * FROM bestellingen where artnr='".$artikelnummer."'";
												$aantalkeer = 0;

												foreach($mysql_connectie->query($sql_count) as $teller) {
													$aantalkeer = ($aantalkeer+$teller['artaantal']);
												}
 												unset($del);

												$del = $mysql_connectie->prepare($sql_count);
												$del->execute();
												$o=$del->rowCount();
												unset($del);	unset($sql_count);



												$OUTPUT_VAR.= "<td class=\"text-center\">".$eenheid."</td>";	
												$OUTPUT_VAR.= "<td class=\"text-right\"><small>".$artikelnummer."</small></td>";
												$OUTPUT_VAR.= "<td class=\"text-right\">".$aantalkeer." / ".$o."</small></td>";
												$OUTPUT_VAR.= "</tr>";	
											}







											$OUTPUT_VAR.= "</tbody></table>";												


								break;
							case "get-stock-listmachines":
									// maakt een table met de laatste magazijn wijzigingen


												$OUTPUT_VAR= "";

											    $sql_mag = "SELECT distinct(category) from machines order by category asc";


											  	$NEWS_LETTER_MODUS = false;

											    if(isset($_GET['n']) && trim($_GET['n'])=="1"){
											    	$NEWS_LETTER_MODUS = true;
											    }


											  
											    $CAT_DD = "<ul style=\"margin-left:-38px;\">";

   

  												$CAT_DD.= "<li><a href=\"javascript:show_cat('*');\" >";
												$CAT_DD.= "All categories";
												$CAT_DD.= "</a></li>";


											    	foreach($mysql_connectie->query($sql_mag) as $c) {

											    		 $link_cat = "javascript:show_cat('".str_replace(" ","_",strtolower($c['category']))."');";

											    		 $sql_order = "SELECT * from machines where category='".$c['category']."'";
												    	 $del = $mysql_connectie->prepare($sql_order);
														 $del->execute();
														 $p=$del->rowCount();
														 unset($del);	


														 $CAT_DD.= "<li>";

														if($p >= 1){
															 $CAT_DD.= "<a href=\"".$link_cat."\" >".$c['category']."</a> ";
														} else {
															 $CAT_DD.= "".$c['category']."";

														}

														
														 $CAT_DD.= "(".$p.")</li>";
											    									


											    	}

												$CAT_DD.= "</ul>";	




											$OUTPUT_VAR.= "<div class=\"row\">";

											$OUTPUT_VAR.= "<div class=\"col-sm-2\">";
											$OUTPUT_VAR.=$CAT_DD;
											$OUTPUT_VAR.=  "</div>";
											$OUTPUT_VAR.= "<div class=\"col-sm-10\">";







											$OUTPUT_VAR.= "<table class=\"table table-bordered table-hover table-sortable\" id=\"machine_table_view\">";
											$OUTPUT_VAR.= "<thead><tr>";

											if($NEWS_LETTER_MODUS){

												$OUTPUT_VAR.= "<th style=\"max-width:10px;\"></th>";


											}


											$OUTPUT_VAR.= "<th ></th><th>Make & model<span class=\"fa fa-sort ri\"></span></th><th></th>";
											$OUTPUT_VAR.= "<th>Uren<span class=\"fa fa-sort ri\"></span></th><th>Bouwjaar<span class=\"fa fa-sort ri\"></span></th>";
											$OUTPUT_VAR.= "<th >Price<span class=\"fa fa-sort ri\"></span></th></tr></thead>";
											$OUTPUT_VAR.= "<tbody>";	




										    $sql_mag = "SELECT * from machines order by id desc";

											foreach($mysql_connectie->query($sql_mag) as $magazijn) {

												$makemodel =  $magazijn['make'] ." - ".$magazijn['model'];
												$yr =  $magazijn['year'];
							 					$ser =  $magazijn['serialno'];
 
							 					$machine_id = $magazijn['machineid'];
							 					  $condition = $magazijn['hours'];
							 					$categ =  $magazijn['category'];
							 					$price =  trim($magazijn['listed']);
							 				 
							 					$sql_mag_data = "SELECT * from invoice_data where machine_id='".$magazijn['id']."' and invoicetype='PROFORMA' order by id desc";		

							 					$invoice_id="0";

		 										if(strtoupper(trim($price))=="RESERVED"){
		 											$price_display="<h5><span class=\"label label-warning\">".strtoupper(trim($price))."</span></h5>";
		 										} else {
		 											$price_display=$price;

		 										}

											 

												foreach($mysql_connectie->query($sql_mag_data) as $ivd) {
							 							$invoice_id = trim($ivd['id']);
							 							$invoice_no = trim($ivd['volgnummer']);
							 							break;
		
							 					}		
											 

							 					///invoice-maker.php?id=16&invoice-type=2

							 					if($invoice_id == "0"){
							 						$btn_type ="default";

							 						$btn_invoice="<a href=\"/?action=add_proforma&id=".$magazijn['id']."\">PROFORMA MAKEN</a>";	
							 						$btn_transport_statement = "<a href=\"#\"  disabled>TRANS. STATEMENT</a>";
							 						$btn_commercial = "<a href=\"#\"  disabled>COMMERCIAL INVOICE</a>";
							 						$btn_cmr = "<a href=\"#\"  disabled>CMR</a>";
							 				

							 					} else {
							 						$btn_type ="success";

							 						$btn_invoice="<a href=\"/invoice-maker.php?id=".$invoice_id."\" target=\"_blank\" >PROFORMA #".$invoice_no."</a>";	
							 						$btn_transport_statement = "<a href=\"/statement-maker.php?id=".$invoice_id."\" target=\"_blank\" >TRANSPORT STATEMENT</a>";
							 						$btn_commercial="<a href=\"/invoice-maker.php?id=".$invoice_id."&invoice-type=2\" target=\"_blank\" >COMMERCIAL #".$invoice_no."</a>";	
							 						$btn_cmr = "<a href=\"/invoice-maker.php?id=".$invoice_id."&invoice-type=3\" target=\"_blank\">CMR</a>";
							 				

							 					}


							 					 

							 					unset($invoice_id);
							 					unset($sql_mag_data);


							 						$image_machine = "img/".$machine_id.".jpg";

													if (file_exists($image_machine)) {
													   $image_machine = "<img src='img/".$machine_id.".jpg' style='width:140px' />";
													} else {
													    $image_machine="";
													}

							 					

											 
												$options_invoices="<div class=\"btn-group\">";
												$options_invoices.="  <button type=\"button\" class=\"btn btn-".$btn_type." dropdown-toggle\" data-toggle=\"dropdown\" aria-haspopup=\"true\" aria-expanded=\"false\">";
												$options_invoices.="Documenten <span class=\"caret\"></span></button>";
												$options_invoices.="<ul class=\"dropdown-menu\">";


							 					$options_invoices.="<li>".$btn_invoice."</i>";
							 					$options_invoices.="<li>".$btn_commercial."</i>";
							 					$options_invoices.="<li>".$btn_transport_statement."</i>";
							 					$options_invoices.="<li>".$btn_cmr."</i>";	
							 	
							 					$options_invoices.="</ul></div>";
											 

												$OUTPUT_VAR.= "<tr class=\"".str_replace(" ","_",strtolower($categ))."\">";



											if($NEWS_LETTER_MODUS){

												$OUTPUT_VAR.= "<td ><label><input type=\"checkbox\" value=\"\"></label></td>";


											}





												$OUTPUT_VAR.= "<td>".$image_machine."</td>";
											
												$OUTPUT_VAR.= "<td>".strtoupper($makemodel)."</td>";	

													$OUTPUT_VAR.= "<td><small>".strtoupper($categ)."</small></td>";
												$OUTPUT_VAR.= "<td>".$condition."</td><td>".$yr."</td><td class=\"text-right\">".$price_display."</td>";	
											// $OUTPUT_VAR.= "<td class=\"text-right\">".$options_invoices."</td>";		
												$OUTPUT_VAR.= "</tr>";	
											}


											$OUTPUT_VAR.= "</tbody></table>";	


											$OUTPUT_VAR.=  "</div>";

											$OUTPUT_VAR.= "</div>";	



								break;
							case "get-relations-list":
									// maakt een table met de laatste magazijn wijzigingen

											$OUTPUT_VAR = "<table class=\"table table-bordered table-hover table-sortable\" id=\"relation_table_view\">";
											$OUTPUT_VAR.= "<thead><tr><th><small>Relatienr</small></th><th>Company<span class=\"fa fa-sort ri\"></span></th><th>Contact<span class=\"fa fa-sort ri\"></span></th>";
											$OUTPUT_VAR.= "<th>Stad<span class=\"fa fa-sort ri\"></span></th><th class=\"text-center\">Land<span class=\"fa fa-sort ri\"></span></th>";
											$OUTPUT_VAR.= "<th class=\"text-right\"><small>VAT/BTW/REG</small><span class=\"fa fa-sort ri\"></span></th></tr></thead>";
											$OUTPUT_VAR.= "<tbody>";	

										    $sql_mag = "SELECT * from relaties order by id desc";

											foreach($mysql_connectie->query($sql_mag) as $magazijn) {

												$naam =  ($magazijn['naambv']);
												$stad =  ($magazijn['stad']);
							 					$land =   ($magazijn['land']);
 												$reg =  ($magazijn['regno']);
 												$contactnaam =  ($magazijn['spokepersonname']);
 												if($reg=="0"){
 													$reg = "None";
 												}

 												$t = "";$e="";

 												if(trim($magazijn['spokepersontel']) !=""){
 														$t = "<span class=\"fa fa-phone ri\"></span>";
 												}

 												if(trim($magazijn['spokepersonemail']) !=""){
 														$e = "<span class=\"fa fa-envelope ri\"></span>";
 												}

												   // $sql_order = "SELECT * from bestellingen where kvk='".$magazijn['kvk']."' group by ordernummer";
													
												//	$del = $mysql_connectie->prepare($sql_order);
												//	$del->execute();
												//	$c=$del->rowCount();
													//unset($del);	

												$OUTPUT_VAR.= "<tr><td><small>".$magazijn['id']."</small></td><td>".$naam."</td>";



												$OUTPUT_VAR.= "<td>".$contactnaam." ".$t.$e."</td>";	
												$OUTPUT_VAR.= "<td>".$stad."</td><td>".$land."</td>";	
											$OUTPUT_VAR.= "<td class=\"text-right\">".$reg."</td>";		

												$OUTPUT_VAR.= "</tr>";	
											}


											$OUTPUT_VAR.= "</tbody></table>";	



								break;
							case "get-suppliers-list":
									// maakt een table met de laatste magazijn wijzigingen

											$OUTPUT_VAR = "<table class=\"table table-bordered table-hover table-sortable\">";
											$OUTPUT_VAR.= "<thead><tr><th></th><th>Naam</th>";
											$OUTPUT_VAR.= "<th class=\"text-center\">Klantnummer</th>";
											$OUTPUT_VAR.= "<th class=\"text-right\">Aant. bestelling.</th></tr></thead>";
											$OUTPUT_VAR.= "<tbody>";	

										    $sql_mag = "SELECT * from leveranciers order by naambv asc";

											foreach($mysql_connectie->query($sql_mag) as $magazijn) {

												$naam =  $magazijn['naambv'];
												$ref =  $magazijn['referentie'];
							 					$tel =  $magazijn['tel1'];
 

												    $sql_order = "SELECT * from bestellingen where kvk='".$magazijn['kvk']."' group by ordernummer";
													
													$del = $mysql_connectie->prepare($sql_order);
													$del->execute();
													$c=$del->rowCount();
													unset($del);	

												$OUTPUT_VAR.= "<tr><td class=\"text-right\">".$tel."</td>";	
												$OUTPUT_VAR.= "<td style=\"width:60%;\">".$naam."</td><td><small>".$ref."</small></td>";	
											$OUTPUT_VAR.= "<td class=\"text-right\"><a href=\"/?action=view_orders&k=".$magazijn['kvk']."\" >".$c."</a></td>";		

												$OUTPUT_VAR.= "</tr>";	
											}


											$OUTPUT_VAR.= "</tbody></table>";	



								break;
							case "get-recent-stock":
									// maakt een table met de laatste magazijn wijzigingen

											$OUTPUT_VAR = "<table class=\"table table-bordered table-hover table-sortable\">";
											$OUTPUT_VAR.= "<thead><tr><th class=\"text-right\"></th><th>Omschrijving</th>";
											$OUTPUT_VAR.= "<th class=\"text-center\">Aantal</th><th>Op</th></tr></thead>";
											$OUTPUT_VAR.= "<tbody>";	

										    $sql_mag = "SELECT * from magazijn_log order by id desc";

											foreach($mysql_connectie->query($sql_mag) as $magazijn) {

												$omschrijving =  $magazijn['omschrijving'];
												$artikelnummer =  $magazijn['artikelnummer'];
												$timestamp = $magazijn['stempel'];
												$aantal = $magazijn['aantal'];
												$eenheid = $magazijn['eenheid'];
												$richting = $magazijn['richting'];
												$medewerker = $magazijn['medewerker'];

												$OUTPUT_VAR.= "<tr>";

												if(trim(strtoupper($richting))=="IN"){
													$timestamp = $magazijn['stempel'];
													$OUTPUT_VAR.= "<td class=\"text-right\"><span class=\"label label-success\">IN</span></td>";
												} else {
													$OUTPUT_VAR.= "<td class=\"text-right\">".$medewerker."&nbsp;<span class=\"label label-danger\">UIT</span></td>";
													$timestamp = $magazijn['uitgifte'];
												}

												$OUTPUT_VAR.= "<td>".$omschrijving."</td>";	
												$OUTPUT_VAR.= "<td class=\"text-center\">".$aantal." ".$eenheid."</td>";	
												$OUTPUT_VAR.= "<td><small>".$timestamp."</small></td>";		

												$OUTPUT_VAR.= "</tr>";	
											}


											$OUTPUT_VAR.= "</tbody></table>";	



								break;
							case "save_stock":

								$artikelenid = ""; //
								$artikelnummer = ""; //
								$timestamp = date("Y-m-d H:i:s");  //
								$aantal = ""; //
								$eenheid = ""; //
								$omschrijving = "";//
								$richting = "IN";//
								$uitgifte = "nvt";
								$medewerker = "onbekend";
								if(isset($_GET['uit']) && trim($_GET['uit']) != ""){$uitgifte = trim($_GET['uit']);}
								if(isset($_GET['human'])){$medewerker = trim($_GET['human']);}
								if(isset($_GET['richting'])){$richting = trim($_GET['richting']);}
								if(isset($_GET['id'])){$artikelenid = trim($_GET['id']);}
								if(isset($_GET['cn'])){$aantal = trim($_GET['cn']);}
								if(isset($_GET['eh'])){$eenheid = strtolower(trim($_GET['eh']));}

											$sql_kvk = "SELECT * from artikelen where id='".trim($artikelenid)."'";
											foreach($mysql_connectie->query($sql_kvk) as $bv) {
												$omschrijving =  $bv['omschrijving'];
												$artikelnummer =  $bv['artikelcode_leverancier'];
												break;
											}



								$sql = "INSERT INTO magazijn_log (artikelenid, artikelnummer, stempel, aantal,eenheid,omschrijving,richting,medewerker,uitgifte) VALUES ('".$artikelenid."', '".$artikelnummer."', '".$timestamp."', '".$aantal."', '".$eenheid."', '".$omschrijving."','".$richting."','".$medewerker."','".$uitgifte."')";

								if ($mysql_connectie->query($sql)) {
		    								$OUTPUT_VAR = "OK";	
								} else {
											$OUTPUT_VAR = "Error - ".$mysql_connectie->errorInfo()[2];
		    					}





		    					

		    					break;	
							case "get-order-list-short":

									$OUTPUT_VAR = "";

									if(isset($_GET['k']) && trim($_GET['k']) != "0"){
										$sql_str1 = "SELECT * from bestellingen where kvk='".trim($_GET['k'])."' GROUP BY ordernummer order by orderdatum ASC";
									} else {
											$sql_str1 = "SELECT * from bestellingen GROUP BY ordernummer order by orderdatum ASC ";
									}



								







									foreach($mysql_connectie->query($sql_str1) as $row) {

											$order_datum = $row['orderdatum'];
											$order_ref = $row['referentie'];
											$order_nr = $row['ordernummer'];

											$sql_kvk = "SELECT * from leveranciers where kvk='".trim($row['kvk'])."'";
											foreach($mysql_connectie->query($sql_kvk) as $bv) {
												$order_leverancier = $bv['naambv'];
												break;
											}
											

											$sql_count ="SELECT * FROM bestellingen where kvk='".trim($row['kvk'])."' and ordernummer='".$order_nr."'" ;

											$del = $mysql_connectie->prepare($sql_count);
											$del->execute();
											$c=$del->rowCount();

											
											
											$order_items= $c;

											$OUTPUT_VAR.= "<tr>";
											$OUTPUT_VAR.= "<td>".$order_datum."</td>";				
											$OUTPUT_VAR.= "<td>".$order_ref."</td>";
											$OUTPUT_VAR.= "<td><a href=\"javascript:show_supplier_details('".trim($row['kvk'])."');\">".$order_leverancier."</a></td>";
											$OUTPUT_VAR.= "<td><a href=\"javascript:show_order_details('".trim($row['kvk'])."','".$order_nr."');\">".$order_nr."</a></td>";
											$OUTPUT_VAR.= "<td class=\"text-center\">".$order_items."</td>";


											$OUTPUT_VAR.= "</tr>";

											unset($sql_kvk);unset($del);unset($sql_count);
									}


									 
							break; 
							case "view-orders-on-kvk":


								// MAAK TABLE MET DETAILS UIT EEN ORDER

								if(!isset($_GET['k'])){
									$kvk=-1;
								} else {
									$kvk = trim($_GET['k']); 
								}


 									$sql_order = "SELECT * from bestellingen where kvk='".$kvk."' group by ordernummer order by orderdatum";


									$OUTPUT_VAR= "<table class=\"table table-bordered table-hover table-sortable\" >";
									$OUTPUT_VAR.= "<tbody>";
						 
									
									foreach($mysql_connectie->query($sql_order) as $row) {
											

											$OUTPUT_VAR.= "<tr><td>".$row['orderdatum']."</td>";
											$OUTPUT_VAR.= "<td>".$row['referentie']."</td>";
											$OUTPUT_VAR.= "<td class=\"text-right\">".$row['ordernummer']."</td></tr>";
									}



								$OUTPUT_VAR.= "</tbody></table>";	
								break;
							case "get-supplier-details":


								// MAAK TABLE MET DETAILS UIT EEN ORDER

								if(!isset($_GET['k'])){
									$kvk=-1;
								} else {
									$kvk = trim($_GET['k']); 
								}


								$OUTPUT_VAR= "<table class=\"table table-bordered table-hover table-sortable\" >";
									$OUTPUT_VAR.= "<tbody>";
									$sql_str1 = "SELECT * from leveranciers where kvk='".$kvk."'";
									
									foreach($mysql_connectie->query($sql_str1) as $row) {
											

										    $sql_order = "SELECT * from bestellingen where kvk='".$row['kvk']."' group by ordernummer";
											
											$del = $mysql_connectie->prepare($sql_order);
											$del->execute();
											$c=$del->rowCount();
											unset($del);	


											$link = "<a href=\"/?action=view_orders&k=".$row['kvk']."\">Bekijk deze</a>";

											$OUTPUT_VAR.= "<tr><td colspan=4 class=\"text-center\"><h4>".$row['naambv']."</h4></td></tr>";
											$OUTPUT_VAR.= "<tr><td>KvK</td><td>".$row['kvk']."</td></tr>";
											$OUTPUT_VAR.= "<tr><td>Klantnummer</td><td>".$row['referentie']."</td></tr>";
											$OUTPUT_VAR.= "<tr><td>Telefoon</td><td>".$row['tel1']."</td></tr>";
											$OUTPUT_VAR.= "<tr><td>Aantal bestellingen</td><td>".$c." ".$link."</td></tr>";

									}



								$OUTPUT_VAR.= "</tbody></table>";	
								
							break; 
							case "get-order-details":


								// MAAK TABLE MET DETAILS UIT EEN ORDER

								if(!isset($_GET['k']) || !isset($_GET['o'])){
									$kvk=-1;
									$ordernr=-1;
								} else {
									$kvk = trim($_GET['k']);$ordernr= trim($_GET['o']);
								}


								$OUTPUT_VAR= "<table cellpadding=4 cellspacing=10 style=\"width:100%;background:none;\">";

									$sql_str1 = "SELECT * from bestellingen where kvk='".$kvk."' and ordernummer='".$ordernr."'";
									$once = 0;	
									foreach($mysql_connectie->query($sql_str1) as $row) {

											if($once==0){
													$once=1;
													$OUTPUT_VAR.= "<tbody><tr><td colspan=4>";

													$OUTPUT_VAR.= "<p>Datum: ".$row['orderdatum']."<br/>Referentie: <span class=\"label label-info\">".$row['referentie']."</span></p>";	


													$OUTPUT_VAR.= "</td></tr>";	
											}



											$OUTPUT_VAR.= "<tr><td style=\"text-align:right;\">&nbsp;".$row['artaantal']."&nbsp;</td><td>&nbsp;".$row['arteenheid']."&nbsp;</td>";
											$OUTPUT_VAR.= "<td>&nbsp;".$row['artdescr']."&nbsp;</td><td style=\"text-align:right;\">".$row['artnr']."&nbsp;</td></tr>";	



									}



								$OUTPUT_VAR.= "</tbody></table>";	
								
							break; 
							case "json-products-order":



								$sql_str = "SELECT * from artikelen order by artikelcode_leverancier ASC";


								$OUTPUT_VAR = "[";

								foreach($mysql_connectie->query($sql_str) as $row) {


									$val2=json_encode($row['omschrijving']);
									$val1=json_encode($row['artikelcode_leverancier']);
									$r = "{\"artnr\": ".$val1.", \"omschr\": ".$val2."}";
								
									if($OUTPUT_VAR=="["){
										$OUTPUT_VAR.= $r;
									} else {
										$OUTPUT_VAR.=",".$r;
									}

								}



								$OUTPUT_VAR.= "]";




								//$OUTPUT_VAR = "json";





						     break; 
							case "checkkvk":

							// CONTROLEREN OF EEN KVK VOORKOMT IN DE DATABASE
								if(!isset($_GET['kvk'])){
									$kvk=-1;
								} else {
									$kvk = trim($_GET['kvk']);
								}

								$count=0;
								$sqlqstr="SELECT * FROM leveranciers where kvk='".$kvk."'";

								$del = $mysql_connectie->prepare($sqlqstr);
								$del->execute();
								$c=$del->rowCount();

								if($c > 0) {
									$res="FOUND"; // bestaat al
								} else {
									$res="EOF";
								}

							 
								$OUTPUT_VAR = $XML_HEAD."<cmd>".$command."</cmd><kvk>".$kvk."</kvk><result>".$res."</result>".$XML_FOOT;	

								unset($kvk);unset($sqlqstr);unset($count);unset($del);

						     break; 
						case "savesupplier":

								// LEVERANCIER OPSLAAN IN DE DATABASE
								// ?call=savesupplier&kvk=1254654+6&name=Max%20Machinery&tel=0625481676&ref=35333543534&note=	
							    $sv_supplier_xml = "";
								$sv_kvk = "none";
								$sv_name = "none";			
								$sv_tel = "none";
								$sv_ref = "none";									

							    if(isset($_GET['kvk']) && trim($_GET['kvk']) != ""){
							    		$sv_kvk = trim($_GET['kvk']);
							    }

							    if(isset($_GET['name']) && trim($_GET['name']) != ""){
							    		$sv_name = trim($_GET['name']);
							    }

							    if(isset($_GET['tel']) && trim($_GET['tel']) != ""){
							    		$sv_tel = trim($_GET['tel']);
							    }

							    if(isset($_GET['ref']) && trim($_GET['ref']) != ""){
							    		$sv_ref = trim($_GET['ref']);
							    }


								$sql = "INSERT INTO leveranciers (kvk, naambv, tel1, referentie) VALUES ('".$sv_kvk."', '".$sv_name."', '".$sv_tel."', '".$sv_ref."')";

								if ($mysql_connectie->query($sql)) {
		    								$sv_supplier_xml.= "<status>OK</status>";	
								} else {
											$sv_supplier_xml.= "<status>FAIL</status>";
		    					}
							
	  							$sv_supplier_xml.= "<kvk><![CDATA[".$sv_kvk."]]></kvk>";							    			
	  							$sv_supplier_xml.= "<name><![CDATA[".$sv_name."]]></name>";	
	  							$sv_supplier_xml.= "<tel><![CDATA[".$sv_tel."]]></tel>";	
	  							$sv_supplier_xml.= "<ref><![CDATA[".$sv_ref."]]></ref>";


								$OUTPUT_VAR = $XML_HEAD."<cmd>".$command."</cmd><result>".$sv_supplier_xml."</result>".$XML_FOOT;	


								unset($sv_supplier_xml);	
								unset($sv_kvk);
								unset($sv_name);			
								unset($sv_tel);
								unset($sv_ref);								

							 break;

						case "get_equipment_invoice":
							// laad buyer info

								$eqid = trim($_GET['id']);



								$sqlqstr="SELECT * FROM machines where id='".$eqid."'";

								$mach_html ="";

								foreach($mysql_connectie->query($sqlqstr) as $machine) {


												$mach_html.= "Make: ".trim(strtoupper($machine['make']))."<br/>";
												$mach_html.= "Model: ".trim(strtoupper($machine['model']))."<br/>";
												$mach_html.= "Serial No. ".trim(strtoupper($machine['serialno']))."<br/>";
												$mach_html.= "Engine No. ".trim(strtoupper($machine['engine_serial']))."<br/>";
												$mach_html.= "Year: ".trim(strtoupper($machine['year']))."<br/>";
												$mach_html.= "Weight: ".trim(strtoupper($machine['weight']))."<br/>";
												$mach_html.= "Country origin: ".trim(strtoupper($machine['country']))."";	
												$mach_html.= "<input type=\"hidden\" name=\"make_model\" value='".trim(strtoupper($machine['model']))."'>";
												break;
								}



								$mach_html.= "</table>";



								$OUTPUT_VAR = $mach_html;


							 break;
						case "get_buyer":
							// laad buyer info

								$buyerid = trim($_GET['id']);



								$sqlqstr="SELECT * FROM relaties where id='".$buyerid."'";

								$buyer_html ="";

								foreach($mysql_connectie->query($sqlqstr) as $bv) {

												$buyer_html.= "".trim(strtoupper($bv['naambv']))."\n";
												$buyer_html.= "".trim(($bv['street']))."<br>";
	
												if(trim($bv['postal']) !="" && trim($bv['postal']) !="0"){
													$buyer_html.= "".trim(($bv['postal']))."<br>";
												}

												if(trim($bv['regio']) !="" && trim($bv['regio']) !="0"){
													$buyer_html.= "".trim(($bv['regio']))."<br>";
												}

												$buyer_html.= "".trim(($bv['stad']))."<br>";
												$buyer_html.= "".trim(($bv['land']))."<br>";

												$buyer_html.= "REG/VAT/BTW: ".trim(($bv['regno']))."";
												break;
								}
								$buyer_html.= "";



								$OUTPUT_VAR = $buyer_html;


							 break;
						case "get_relations":
							// CONTROLEREN OF EEN KVK VOORKOMT IN DE DATABASE
						 


 								$res="list";
								$sqlqstr="SELECT * FROM relaties order by naambv asc";
								$XML_RESULT_NODE="";
								    			    foreach($mysql_connectie->query($sqlqstr) as $row) {

													      
													     			$XML_RESULT_NODE.= "<buyer><id>".$row['id']."</id>";
													     			$XML_RESULT_NODE.= "<name>".$row['naambv']."</name>";	
																    $XML_RESULT_NODE.= "<reg>".$row['regno']."</reg>";
																    $XML_RESULT_NODE.= "</buyer>";	
												
								    				}								

							 	unset($sqlqstr);
								$OUTPUT_VAR = $XML_HEAD."<cmd>".$command."</cmd><result>".$XML_RESULT_NODE."</result>".$XML_FOOT;	


						     break; 
						case "get_products":

							// CONTROLEREN OF EEN KVK VOORKOMT IN DE DATABASE
						 
 								$res="list";
								$sqlqstr="SELECT * FROM artikelen order by omschrijving asc";
								$XML_RESULT_NODE="";
								    			    foreach($mysql_connectie->query($sqlqstr) as $row) {

													      
													     			$XML_RESULT_NODE.= "<product><id>".$row['id']."</id>";
													     			$XML_RESULT_NODE.= "<name>".$row['omschrijving']."</name>";	
																    $XML_RESULT_NODE.= "<artnr>".$row['artikelcode_leverancier']."</artnr>";
																    $XML_RESULT_NODE.= "</product>";	
												
								    				}								

							 	unset($sqlqstr);
								$OUTPUT_VAR = $XML_HEAD."<cmd>".$command."</cmd><result>".$XML_RESULT_NODE."</result>".$XML_FOOT;	
						     break; 
						case "get_suppliers":

							// CONTROLEREN OF EEN KVK VOORKOMT IN DE DATABASE
						 
 								$res="list";
								$sqlqstr="SELECT * FROM leveranciers order by naambv asc";
								$XML_RESULT_NODE="";
								    			    foreach($mysql_connectie->query($sqlqstr) as $row) {



				      
													     			$XML_RESULT_NODE.= "<supplier><id>".$row['id']."</id>";	
																    $XML_RESULT_NODE.= "<kvk>".$row['kvk']."</kvk>";
																    $XML_RESULT_NODE.= "<name>".$row['naambv']."</name>";
																    $XML_RESULT_NODE.= "<ref>".$row['referentie']."</ref></supplier>";	
												
								    				}								

							 	unset($sqlqstr);
								$OUTPUT_VAR = $XML_HEAD."<cmd>".$command."</cmd><result>".$XML_RESULT_NODE."</result>".$XML_FOOT;	
						     break; 
						}	
					// ----------------------------------------------------------------------------------
  	} else {

  			$OUTPUT_VAR = $XML_HEAD."<result>null</result>".$XML_FOOT;

  	}




 } // post_data
// ----------------------------------------------------------------------------------	

	//header("Content-type: text/xml");
    echo $OUTPUT_VAR;


	$mysql_connectie=null;
	unset($mysql_connectie);

?>