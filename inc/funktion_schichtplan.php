<?php

/*#######################################################
#       Aufbau von Standart Feldern                     #
#######################################################*/

// erstellt ein Array der Reume
	$sql = "SELECT `RID`, `Name` FROM `Room` ".
		"WHERE `Show`='Y'". 
		"ORDER BY `Number`, `Name`;";
	
	$Erg = mysql_query($sql, $con);
	$rowcount = mysql_num_rows($Erg);

	for ($i=0; $i<$rowcount; $i++)
	{
		$Room[$i]["RID"]  = mysql_result($Erg, $i, "RID");
		$Room[$i]["Name"] = mysql_result($Erg, $i, "Name");
	
		$RoomID[ mysql_result($Erg, $i, "RID") ] =  mysql_result($Erg, $i, "Name");
	}

// erstellt ein Aray der Engeltypen
	$sql = "SELECT `TID`, `Name` FROM `EngelType` ORDER BY `Name`";
	$Erg = mysql_query($sql, $con);
	$rowcount = mysql_num_rows($Erg);
	for ($i=0; $i<$rowcount; $i++)
	{
		$EngelType[$i]["TID"]  = mysql_result($Erg, $i, "TID");
		$EngelType[$i]["Name"]  = mysql_result($Erg, $i, "Name").Get_Text("inc_schicht_engel");

		$EngelTypeID[ mysql_result($Erg, $i, "TID") ] = 
			mysql_result($Erg, $i, "Name").Get_Text("inc_schicht_engel");
	}												


/*#######################################################
#	gibt die engelschischten aus			#
#######################################################*/
function ausgabe_Feld_Inhalt( $SID, $Man ) 
{
// gibt, nach �bergabe der der SchichtID (SID) und der RaumBeschreibung,
// die eingetragenden und und offenden Schichteint�ge zur�ck
	global $EngelType, $EngelTypeID, $con;
	//form Config
	global $CCC_Start, $CCC_End, $DEBUG;

	
	///////////////////////////////////////////////////////////////////
	// Schow Admin Page
	///////////////////////////////////////////////////////////////////
	if( $_SESSION['CVS'][ "admin/schichtplan.php" ] == "Y" )
	{
		$Spalten.= "<a href=\"./../admin/schichtplan.php?action=change&SID=$SID\">edit</a><br>\n\t\t";
	}
	

	///////////////////////////////////////////////////////////////////
  	// Ausgabe des Schischtnamens
	///////////////////////////////////////////////////////////////////
	if( isset($CCC_Start) && $SID<10000) 
		$Spalten.="<a href=\"$CCC_Start$SID$CCC_End\" target=\"_black\"><u>$Man:</u></a><br>";
	else
  		$Spalten.="<u>".$Man.":</u><br>";


	///////////////////////////////////////////////////////////////////
	// SQL abfrage f�r die ben�tigten schichten
	///////////////////////////////////////////////////////////////////
	$SQL = "SELECT * FROM `ShiftEntry` WHERE (`SID` = '$SID') ORDER BY `TID`, `UID` DESC ;";
	$Erg = mysql_query($SQL, $con);
	
	$Anzahl = mysql_num_rows($Erg);
	$Feld=0;
	for( $i = 0; $i < $Anzahl; $i++ )
	{
		$Temp_TID_old = $Temp[$Feld]["TID"];
		$Temp_UID_old = $Temp[$Feld]["UID"];
		
		$Temp_TID = mysql_result($Erg, $i, "TID");
		
		// wenn sich der Type �ndert wird zumn�sten feld geweckselt
		if( $Temp_TID_old != $Temp_TID )
			$Feld++;
			
		$Temp[$Feld]["TID"] = $Temp_TID;
		$Temp[$Feld]["UID"] = mysql_result($Erg, $i, "UID");
		
		// sonderfall ersten durchlauf
		if( $i == 0 )
		{
			$Temp_TID_old = $Temp[$Feld]["TID"];
			$Temp_UID_old = $Temp[$Feld]["UID"];
		}
		
		// ist es eine zu vergeben schicht?
		if( $Temp[$Feld]["UID"] == 0 )
			$Temp[$Feld]["free"]++;
		else
			$Temp[$Feld]["Engel"][] = $Temp[$Feld]["UID"];
	} // FOR
	

	///////////////////////////////////////////////////////////////////
	// Aus gabe der Schicht
	///////////////////////////////////////////////////////////////////
	if( count($Temp) )
	  foreach( $Temp as $TempEntry => $TempValue )
	  {
		// ausgabe EngelType
		$Spalten.= $EngelTypeID[ $TempValue["TID"] ]. " ";
		
		// ausgabe Eingetragener Engel
		if( count($TempValue["Engel"]) > 0  )
		{
			if( count($TempValue["Engel"]) == 1  )
				$Spalten.= Get_Text("inc_schicht_ist"). ":<br>\n\t\t";
			else 
				$Spalten.= Get_Text("inc_schicht_sind"). ":<br>\n\t\t";
			
			foreach( $TempValue["Engel"] as $TempEngelEntry=> $TempEngelID )
      				$Spalten.= "&nbsp;&nbsp;". UID2Nick( $TempEngelID ).
      					   DisplayAvatar( $TempEngelID ).
					   "<br>\n\t\t";
			$Spalten = substr( $Spalten, 0, strlen($Spalten)-7 );
		}
		
		// ausgabe ben�tigter Engel
		////////////////////////////
		//in vergangenheit
		$SQLtime = "SELECT `DateS` FROM `Shifts` WHERE (SID='$SID' AND `DateS`> '". 
			gmdate("Y-m-d H:i:s", time()+ 3600). "')";
		$Ergtime = mysql_query($SQLtime, $con);
		if( mysql_num_rows( $Ergtime))
		{
		 //mit sonder status
		 $SQLerlaubnis = "SELECT Name FROM `EngelType` WHERE TID = '". $TempValue["TID"]. "'";
		 $Ergerlaubnis =  mysql_query( $SQLerlaubnis, $con);
		 if( mysql_num_rows( $Ergerlaubnis))
		  if( $_SESSION['CVS'][mysql_result( $Ergerlaubnis, 0, "Name")] == "Y" ||
			$_SESSION['CVS'][mysql_result( $Ergerlaubnis, 0, "Name")] == "")
		    if( $TempValue["free"] > 0 )
		    {
			$Spalten.= "<br>\n\t\t&nbsp;&nbsp;<a href=\"./schichtplan_add.php?SID=$SID&TID=".
				   $TempValue["TID"]."\">";
			$Spalten.= $TempValue["free"];
			if( $TempValue["free"] == 1 )
				$Spalten.= Get_Text("inc_schicht_weitere").
    					   " ".Get_Text("inc_schicht_Engel").
    					   Get_Text("inc_schicht_wird");
			else
				$Spalten.= Get_Text("inc_schicht_weiterer").
    					   " ".Get_Text("inc_schicht_Engel").
					   Get_Text("inc_schicht_werden");
			$Spalten.= Get_Text("inc_schicht_noch_gesucht");
			$Spalten.= "</a>";
		   }   
		}
		else
		{
			if( $TempValue["free"] > 0 )
				$Spalten.= "<br>\n\t\t&nbsp;&nbsp;<h3><a>Fehlen noch: ". $TempValue["free"]. "</a></h3>";
		}
		$Spalten.= "<br>\n\t\t";
	
	} // FOREACH
	return $Spalten;
} // function Ausgabe_Feld_Inhalt



/*#######################################################
#	gibt die engelschischten Druckergerecht aus	#
#######################################################*/
function Ausgabe_Feld_Inhalt_Druck($RID, $Man ) 
{
// gibt, nach �bergabe der der SchichtID (SID) und der RaumBeschreibung,
// die eingetragenden und und offenden Schichteint�ge zur�ck


} // function Ausgabe_Feld_Inhalt




/*#######################################################
#	Ausgabe der Raum Spalten			#
#######################################################*/
function CreateRoomShifts( $raum )
{
	global $Spalten, $ausdatum, $con, $DEBUG, $GlobalZeileProStunde;
	
	$ZeitZeiger = 0;
	
	$SQLSonder = "SELECT `SID`, `DateS`, `Len`, `Man` FROM `Shifts` ".
		     "WHERE ((`RID` = '$raum') AND (`DateE` like '$ausdatum%') AND ".
		     	"(`DateS` < '$ausdatum') ) ORDER BY `DateS`;";
      	$ErgSonder = mysql_query($SQLSonder, $con);
	if( (mysql_num_rows( $ErgSonder) > 1) )
	{
		echo Get_Text("pub_schichtplan_colision"). " ".
			mysql_result($ErgSonder, $i, "DateS"). 
			" '". mysql_result($ErgSonder, $i, "Man"). "' ".
			" (".  mysql_result($ErgSonder, $i, "SID"). " R$raum)###<br><br>";
	}
	elseif( (mysql_num_rows( $ErgSonder) == 1) )
	{
		
		$ZeitZeiger =	substr( mysql_result($ErgSonder, 0, "DateS"), 11, 2 )+
				(substr( mysql_result($ErgSonder, 0, "DateS"), 14, 2 ) / 60)+
				mysql_result($ErgSonder, 0, "Len") - 24;
		if( $ZeitZeiger > 0)
		       	$Spalten[0].= 
				"\t\t<td valign=\"top\" rowspan=\"". ($ZeitZeiger * $GlobalZeileProStunde). "\">\n".
				"\t\t\t<h3>&uarr;&uarr;&uarr;</h3>".
	        		Ausgabe_Feld_Inhalt( mysql_result($ErgSonder, 0, "SID"), 
						     mysql_result($ErgSonder, 0, "Man") ).
	       			"\n\t\t</td>\n";
		
		
	}
		
		     
	$SQL = "SELECT `SID`, `DateS`, `Len`, `Man` FROM `Shifts` ".
	       "WHERE ((`RID` = '$raum') and (`DateS` like '$ausdatum%')) ORDER BY `DateS`;";
      	$Erg = mysql_query($SQL, $con);

	for( $i = 0; $i < mysql_num_rows($Erg); ++$i )
	{
		$ZeitPos = substr( mysql_result($Erg, $i, "DateS"), 11, 2 )+
			  (substr( mysql_result($Erg, $i, "DateS"), 14, 2 ) / 60);
		$len = mysql_result($Erg, $i, "Len");
		
		if( $ZeitZeiger < $ZeitPos  )
		{
	       		$Spalten[$ZeitZeiger * $GlobalZeileProStunde].=	
				"\t\t<td valign=\"top\" rowspan=\"". 
				( ($ZeitPos - $ZeitZeiger ) * $GlobalZeileProStunde ). 
				"\">&nbsp;</td>\n";
			$ZeitZeiger += $ZeitPos - $ZeitZeiger;
		}
		if($ZeitZeiger == $ZeitPos )
		{
	       		$Spalten[$ZeitZeiger * $GlobalZeileProStunde].= 
					"\t\t<td valign=\"top\" rowspan=\"". ( $len * $GlobalZeileProStunde). "\">\n".
					"\t\t\t".
	        			Ausgabe_Feld_Inhalt( mysql_result($Erg, $i, "SID"), 
							     mysql_result($Erg, $i, "Man") ).
	       				"\n\t\t</td>\n";
			$ZeitZeiger += $len;
		}
		else
		{
			echo Get_Text("pub_schichtplan_colision"). " ".
				mysql_result($Erg, $i, "DateS"). 
				" '". mysql_result($Erg, $i, "Man"). "' ".
				" (".  mysql_result($Erg, $i, "SID"). " R$raum)<br><br>";
		}
	}
	if( $ZeitZeiger <= 24 )
       		$Spalten[$ZeitZeiger * $GlobalZeileProStunde].=	
					"\t\t<td valign=\"top\" rowspan=\"". 
					((24 - $ZeitZeiger) * $GlobalZeileProStunde ). 
					"\">&nbsp;</td>\n";
} // function CreateRoomShifts


/*#######################################################
#	Ausgabe der freien schichten			#
#######################################################*/
function showEmptyShifts( )
{
	global $con, $DEBUG, $RoomID;

	echo "<table border=\"1\">\n";
	echo "<tr>\n";
	echo "\t<th>". Get_Text("inc_schicht_date"). "</th>\n";
	echo "\t<th>". Get_Text("inc_schicht_time"). "</th>\n";
	echo "\t<th>". Get_Text("inc_schicht_room"). "</th>\n";
	echo "\t<th>". Get_Text("inc_schicht_commend"). "</th>\n";
	echo "</tr>\n";
	
	$sql = "SELECT `SID`, `DateS`, `Man`, `RID` FROM `Shifts` ".
		"WHERE (`Shifts`.`DateS`>='". gmdate("Y-m-d H:i:s", time()+3600). "') ".
		"ORDER BY `DateS`, `RID`;";
	$Erg = mysql_query($sql, $con);

	$angezeigt = 0;
	for ($i=0; ($i<mysql_num_rows($Erg)) && ($angezeigt< 15); $i++)
	   if( $RoomID[mysql_result( $Erg, $i, "RID")]!="" )
	   {
 		$Sql2 = "SELECT `UID` FROM `ShiftEntry` ".
			"WHERE `SID`=". mysql_result( $Erg, $i, "SID"). " AND ".
				"`UID`='0';";
		$Erg2 = mysql_query($Sql2, $con);
		
		if( mysql_num_rows($Erg2)>0)
		{
			$angezeigt++;
			echo "<tr>\n";
			echo "\t<td>". substr(mysql_result( $Erg, $i, "DateS"), 0, 10). "</td>\n";
			echo "\t<td>". substr(mysql_result( $Erg, $i, "DateS"), 11). "</td>\n";
			echo "\t<td>". $RoomID[mysql_result( $Erg, $i, "RID")]. "</td>\n";
			echo "\t<td>". 
				ausgabe_Feld_Inhalt( mysql_result( $Erg, $i, "SID"), mysql_result( $Erg, $i, "Man")).
				"</td>\n";
			echo "</tr>\n";
		}
	   }
	
	echo "</table>\n";
	
} //function showEmptyShifts

	
/*#######################################################
#	Gibt die anzahl der Schichten im Raum zur�ck	#
#######################################################*/
function SummRoomShifts( $raum )
{
	global $ausdatum, $con, $DEBUG, $GlobalZeileProStunde;
	
	$SQLSonder = "SELECT `SID`, `DateS`, `Len`, `Man` FROM `Shifts` ".
		     "WHERE ((`RID` = '$raum') AND (`DateE` like '$ausdatum%') AND ".
		     	"(`DateS` like '$ausdatum%') ) ORDER BY `DateS`;";
      	$ErgSonder = mysql_query($SQLSonder, $con);
	
	return mysql_num_rows($ErgSonder);
}

?>
