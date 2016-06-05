<?php
define("MYSQL_SERVER", "");
define("MYSQL_USER", "");
define("MYSQL_PASSWORD", "");
define("MYSQL_DB", "");
define("MAIN_TABLE_NAME", "annuaire");
define("LOG_IP", false);
define("DELETE_ALL_LOGGED_IP", false);
define("CREATE_DB_IF_NOT_EXISTS", true);
define("CREATE_TABLES_IF_NOT_EXIST", true);
define("TITLE","RW-PHPLinks v1.0<br/>Annuaire de sites");
define("SUBTITLE","Annuaire de sites");

if (isset($_POST['checkurl'])){
	$url = $_POST['checkurl'];
	
	$homepage = file_get_contents($url);
	$array = explode("<title>", $homepage);
	
	if (count($array>0)){
		//echo 'reply=' . $array[1];
		$subarray = explode("</title>", $array[1]);
		if (count($subarray)>0) {
			$title = $subarray[0];
			echo $title;
			exit;
		}
	}
	
	echo '';
	exit;
}

// Allowed ip for adding a website : Evolution to do : map to an account
$allowed_ip = "78.201.68.2";
$client_ip = $_SERVER['REMOTE_ADDR'];
//echo "<center>your ip = " . $client_ip . "</center></br/>";	

if ($client_ip == $allowed_ip){
	$allowed = true;
}

// supprimer toutes les données de la table annuaire si paramètre get delete_data = true
$delete_data = false;
if (isset($_GET['delete_data'])){
	if (trim(strtolower($_GET['delete_data'])) == "true"){
		$delete_data = true;
	}
}

if ($delete_data == true){
	// DELETE ALL DATA
	$db = new mysqli(MYSQL_SERVER, MYSQL_USER, MYSQL_PASSWORD, MYSQL_DB);
	if ($db->connect_errno) {
	    exit;
	}
	$sql = "DELETE FROM `" . MAIN_TABLE_NAME . "`";
	$r = mysqli_query($db, $sql);
	$db->close();
	echo 'data deleted';
}

// supprimer toutes les données de la table ip_address_log si paramètre get delete_ip = true
$delete_ip = false;
if (isset($_GET['delete_ip'])){
	if (trim(strtolower($_GET['delete_ip'])) == "true"){
		$delete_ip = true;
	}
}

if ($delete_ip == true){
	// DELETE ALL IP
	$db = new mysqli(MYSQL_SERVER, MYSQL_USER, MYSQL_PASSWORD, MYSQL_DB);
	if ($db->connect_errno) {
	    exit;
	}
	$r = mysqli_query($db, "delete from ip_address_log");
	$db->close();
	echo 'ip deleted';
}

// si le paramètre CREATE_DB_IF_NOT_EXISTS est défini à true alors tenter de créer la base de données dans paramètre MYSQL_DB
if (CREATE_DB_IF_NOT_EXISTS == true){
	// CREATE DB IF NOT EXISTS
	$db = new mysqli(MYSQL_SERVER, MYSQL_USER, MYSQL_PASSWORD);
	if ($db->connect_errno) {
	    exit;
	}
	$r = mysqli_query($db, "create database if not exists " . MYSQL_DB);
	$db->close();
}

// si le paramètre existe alors tenter de créer les tables annuaire et ip_address_log
if (CREATE_TABLES_IF_NOT_EXIST == true){
	// CREATE TABLE IF NOT EXISTS
	$db = new mysqli(MYSQL_SERVER, MYSQL_USER, MYSQL_PASSWORD, MYSQL_DB);
	if ($db->connect_errno) {
	    exit;
	}
	$sql = "CREATE TABLE IF NOT EXISTS `" . MAIN_TABLE_NAME . "` (`id` bigint(20) NOT NULL AUTO_INCREMENT, `url` varchar(1024) COLLATE latin1_general_ci NOT NULL, `description` varchar(256) COLLATE latin1_general_ci NOT NULL, `titre` varchar(64) COLLATE latin1_general_ci NOT NULL, PRIMARY KEY (`id`) ) ENGINE=MyISAM  DEFAULT CHARSET=latin1 COLLATE=latin1_general_ci";
	$r = mysqli_query($db, $sql);
	
	$sql = "CREATE TABLE `ip_address_log` (`id` bigint(20) NOT NULL AUTO_INCREMENT, `access_date_time` datetime NOT NULL, `ip_address` varchar(32) COLLATE latin1_general_ci NOT NULL, `url` varchar(255) COLLATE latin1_general_ci DEFAULT NULL, PRIMARY KEY (`id`)) ENGINE=MyISAM  DEFAULT CHARSET=latin1 COLLATE=latin1_general_ci";
	$r = mysqli_query($db, $sql);

	$db->close();
}

// LOG IP si paramètre LOG_IP = true
if (LOG_IP==true){
	$db = new mysqli(MYSQL_SERVER, MYSQL_USER, MYSQL_PASSWORD, MYSQL_DB);
	if ($db->connect_errno) {
	    exit;
	}
	$client_ip = $_SERVER['REMOTE_ADDR'];
	$url = $_SERVER['PHP_SELF'];
	$r = mysqli_query($db, "insert into ip_address_log(ip_address, access_date_time, url) values ('" . $client_ip . "',NOW(),'" . $url . "')");
	$db->close();
}

// Sauvegarde du nom de cette page pour définition des url de post
if (!isset($_POST['current_file_name']))
{
	$_POST['current_file_name'] = getCurrentFileName();
	//echo "current file name = " . $_POST['current_file_name'];
}

if ( isset($_GET['delete_url']) && isset($_GET['id']) ){
	$id=$_GET['id'];
	
	$db = new mysqli(MYSQL_SERVER, MYSQL_USER, MYSQL_PASSWORD, MYSQL_DB);
	if ($db->connect_errno) {
		echo 'error';
	    exit;
	}
	   
	$r = mysqli_query($db, "delete from " . MAIN_TABLE_NAME . " where id = " . $id);
}

// AJOUT SITE
if (isset($_POST['url']) && isset($_POST['titre']) && isset($_POST['description'])){
	$url = $_POST['url'];
	$titre = $_POST['titre'];
	$description = $_POST['description'];
    echo "url = " . $url . '<br/>';

   //if ( (trim($url)!='') && (trim($titre) !='') && (trim($description) !='') )
   if ( (trim($url)!='') && (trim($titre) !='') )
   {
	   $db = new mysqli(MYSQL_SERVER, MYSQL_USER, MYSQL_PASSWORD, MYSQL_DB);
	   if ($db->connect_errno) {
	       echo 'error';
	       exit;
	   }
	   
	   $r = mysqli_query($db, "select * from " . MAIN_TABLE_NAME . " where url = '" . $url . "'");
	   if ($r->num_rows>0){
	   		$r = mysqli_query($db, "delete from " . MAIN_TABLE_NAME . " where url = '" . $url . "'");
	   }
	
	   $r = mysqli_query($db, "insert into " . MAIN_TABLE_NAME . " (url, titre, description) values ( '" . $url . "', '" . $titre . "', '" . $description .  "')");
	   $db->close();
	
	   $_POST['url'] = "";
	   $_POST['titre'] = "";
	   $_POST['description'] = "";
   
   }
}


function getCurrentFileName()
{
	if (!isset($_SERVER['REQUEST_URI'])) {
		return "";
	}
	$uri = $_SERVER['REQUEST_URI'];
	$array = explode("/", $uri);
	if (!is_null($array))
	{
		if (count($array)>0)
		{
			$nb = count($array);
			return $array[$nb-1];
		}
	}
}


?>

<!DOCTYPE html>
<html lang="fr">
<head>
  <title><?php echo TITLE; ?></title>
  <meta charset="ISO-8859-1"/>
  <meta name="viewport" content="width=device-width, initial-scale=1"/>
  <link rel="stylesheet" href="http://maxcdn.bootstrapcdn.com/bootstrap/3.3.6/css/bootstrap.min.css">
  <script src="https://ajax.googleapis.com/ajax/libs/jquery/1.12.2/jquery.min.js"></script>
  <script src="http://maxcdn.bootstrapcdn.com/bootstrap/3.3.6/js/bootstrap.min.js"></script>
  
  <!--<script src="//code.jquery.com/jquery-1.10.2.js"></script>-->
  <script src="//code.jquery.com/ui/1.11.4/jquery-ui.js"></script>

</head>
<body>

<div class="container">
  <div class="jumbotron">
  	<center>
    <h1><?php echo TITLE; ?></h1>
    <p><?php echo SUBTITLE; ?></p> 
    </center>
  </div>

<?php

$db = new mysqli(MYSQL_SERVER, MYSQL_USER, MYSQL_PASSWORD, MYSQL_DB);
if ($db->connect_errno) {
    exit;
}

$r = mysqli_query($db, "select * from " . MAIN_TABLE_NAME);
echo '<center>Il y a actuellement ' . $r->num_rows . ' sites enregistrés dans notre base.</center><br/>';

echo '<center>';

if ($r->num_rows > 0) {
    while($row = $r->fetch_assoc()) {
	    //echo '(' . $row["id"]. ') ' . '<a href="' . $row["url"] . '">' . $row["titre"] . '</a> - ' . $row["description"] . '<br/>';
	    echo '<a href="' . $row["url"] . '">' . $row["titre"] . '</a>';
	    if (trim($row["description"]) != ''){
	    	echo ' - ' . $row["description"];
	    }
	    if ($allowed){
	    	echo ' - ' . $row["id"];
	    	echo ' <a href="?delete_url&id=' . $row["id"] . '">delete</a>';
	    }
	    echo '<br/>';
    }
} else {
    //echo "0 results";
}

echo '</center>';


echo $site['url'];

$db->close();

?>

<br/><br/><br/>

<?php

if ($allowed == true){
	
echo '<center><b>Ajoutez votre site :</b><br/><br/>';
echo "<form action='" . $_POST['current_file_name'] . "' method='post'>";
echo ' URL du site: <input type="text" id="url" name="url" value="" size=64><br/>';
echo ' Titre du site: <input type="text" id="titre" name="titre" value="" size=64>';
echo ' <button id="btn001" onclick="">Get title</button>';
echo '<br/>';
echo '  Description du site: <input type="text" name="description" value="" size=64><br><br>';
echo '  <input type="submit" value="Submit">';
echo '</form></center>'; 
}
?>

<script>

$( document ).ready(function() {
	$( "#btn001" ).click(function( event ) {
		event.preventDefault();
		mafonction();
	});
});


function mafonction()
{
	url = $("#url").val();
	console.log(url);
	$.post
	( 	"",
		{ checkurl : url },
		function(data) 
		{
			console.log( data);
			$("#titre").val(data);
		}
	);
}


</script>

</body>
</html>

