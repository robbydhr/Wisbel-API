<?php


	require 'connection.php';
	getAllData();
	


function getAllData(){
	global $con;
	$sql = "select * from alternatif_wisata_belanja;";
	
	$result = mysqli_query($con,$sql);

	$response = array();

	while($row = mysqli_fetch_array($result))
	{
		array_push($response,array("id"=>$row[0],"nama_tempat"=>$row[1],"rating"=>$row[2],"ulasan"=>$row[3],"jam_buka"=>$row[4],"jam_tutup"=>$row[5],"lat"=>$row[6],"long"=>$row[7],"status"=>$row[8],"alamat"=>$row[9]));
	}

	echo json_encode(array("server_response"=>$response));

	mysqli_close($con);
}
?>