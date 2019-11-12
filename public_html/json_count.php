<?php
if($_SERVER["REQUEST_METHOD"]=="POST"){
	require 'connection.php';
	getAllData();
}

function getAllData(){
    global $con;
    
    $lat = "-7.9536916";
    $lng = "112.6124845";
    
    // $r = "35";
    // $u = "40";
    // $w = "20";
    // $j = "80";
    
    // $s_mall = "true";
    // $s_swalayan = "true";
    // $s_pasar = "true";
    
//     //mendapatkan data lat dan long
//     $lat = $_POST["my_lat"];
// 	$lng = $_POST["my_lng"];
    
    //mendapatkan bobot kriteria
    $r = $_POST["b_rating"];
    $u = $_POST["b_ulasan"];
    $w = $_POST["b_waktu"];
    $j = $_POST["b_jarak"];
    
    //mendapatkan alternatif kriteria
    $s_mall = $_POST["s_mall"];
    $s_swalayan = $_POST["s_swalayan"];
    $s_pasar = $_POST["s_pasar"];
    
    $status = array();

    if($s_mall=="true"){
        array_push($status, "mall");
    }
    if($s_swalayan=="true"){
        array_push($status, "swalayan");
    }
    if($s_pasar=="true"){
        array_push($status, "pasar");
    }

    $input_rating = intval($r);
    $input_ulasan = intval($u);
    $input_waktu_operasional = intval($w);
    $input_jarak = intval($j);

    $ids = join("', '",$status);
    
    $sql = "SELECT id, rating, ulasan, jam_buka, jam_tutup, status, (6371 * ACOS(SIN(RADIANS(lat)) * SIN(RADIANS($lat)) + COS(RADIANS(lng - $lng)) * COS(RADIANS(lat)) * COS(RADIANS($lat)))) AS jarak FROM alternatif_wisata_belanja WHERE status IN ('$ids')";

    $result = mysqli_query($con,$sql);
    
    $response = array();
    
    $id = array();

    $rating = array();
    $ulasan = array();
    $waktu_operasional = array();
    $jarak = array();
    
    $n_rating = array();
    $n_ulasan = array();
    $n_waktu_operasional = array();
    $n_jarak = array();
    
    $t_rating = array();
    $t_ulasan = array();
    $t_waktu_operasional = array();
    $t_jarak = array();
    
    $si_plus = array();
    $si_min = array();
    $sum_si = array();
    
    $ci_plus = array();

    $p_rating = 0;
    $p_ulasan = 0;
    $p_waktu_operasional = 0;
    $p_jarak = 0;
    
    $length=0;

    while($row = mysqli_fetch_array($result))
    {
    	$length++;
    	array_push($id, $row['id']);
    	array_push($rating, $row['rating']);
    	array_push($ulasan, $row['ulasan']);
    	
    	//menghitung waktu operasional
    	$buka_jam = intVal(substr($row['jam_buka'],0,2));
    	$buka_menit = intVal(substr($row['jam_buka'],3,2));
    	$tutup_jam = intVal(substr($row['jam_tutup'],0,2));
    	$tutup_menit = intVal(substr($row['jam_tutup'],3,2));
    	
    	$date_awal  = new DateTime($buka_jam.":".$buka_menit);
        $date_akhir = new DateTime($tutup_jam.":".$tutup_menit);
        $selisih = $date_akhir->diff($date_awal);
    
        $jam = $selisih->format('%h');
        $menit = $selisih->format('%i');
     
        if($menit >= 0 && $menit <= 9){
            $menit = "0".$menit;
        }
        
        $hasil = $jam.".".$menit;
        if($hasil == 0){
            $hasil=24;
        }
        $hasil = number_format($hasil,2);
    	array_push($waktu_operasional, $hasil);
    	
    	array_push($jarak, $row['jarak']);
    
    	$p_rating += ((float) pow($row['rating'],2));
    	$p_ulasan += ((float) pow($row['ulasan'],2));
    	$p_waktu_operasional += ((float) pow($hasil,2));
    	$p_jarak += ((float) pow($row['jarak'],2));
    	
    }

    //menghitung pembagi kriteria
    $p_rating = sqrt($p_rating);
    $p_ulasan = sqrt($p_ulasan);
    $p_waktu_operasional = sqrt($p_waktu_operasional);
    $p_jarak = sqrt($p_jarak);
    
    //menghitung normalisasi & terbobot decision matrix
    for ($i = 0; $i < $length; $i++) {
        array_push($n_rating, $rating[$i] / $p_rating);
        array_push($t_rating, $n_rating[$i] * $input_rating);
        array_push($n_ulasan, $ulasan[$i] / $p_ulasan);
        array_push($t_ulasan, $n_ulasan[$i] * $input_ulasan);
        array_push($n_waktu_operasional, $waktu_operasional[$i] / $p_waktu_operasional);
        array_push($t_waktu_operasional, $n_waktu_operasional[$i] * $input_waktu_operasional);
        array_push($n_jarak, $jarak[$i] / $p_jarak);
        array_push($t_jarak, $n_jarak[$i] * $input_jarak);
    } 
    $a_t_rating = $t_rating;
    $a_t_ulasan = $t_ulasan;
    $a_t_waktu_operasional = $t_waktu_operasional;
    $a_t_jarak = $t_jarak;
    
    sort($t_rating);
    sort($t_ulasan);
    sort($t_waktu_operasional);
    sort($t_jarak);

    //menentukan solusi ideal negatif dan solusi ideal positif (A+ & A-)
    $a_plus_rating = $t_rating[$length-1];
    $a_plus_ulasan = $t_ulasan[$length-1];
    $a_plus_waktu_operasional = $t_waktu_operasional[$length-1];
    $a_plus_jarak = $t_jarak[$length-1];
    $a_min_rating = $t_rating[0];
    $a_min_ulasan = $t_ulasan[0];
    $a_min_waktu_operasional = $t_waktu_operasional[0];
    $a_min_jarak = $t_jarak[0];

    //menghitung separasi solusi ideal positif (Si+)
    for ($i = 0; $i < $length; $i++) {
    	$k_rating = $a_plus_rating - $a_t_rating[$i];
    	$k_ulasan = $a_plus_ulasan - $a_t_ulasan[$i];
    	$k_waktu = $a_plus_waktu_operasional - $a_t_waktu_operasional[$i];
    	$k_jarak = $a_min_jarak - $a_t_jarak[$i];
    	
    	$b = pow($k_rating,2)+pow($k_ulasan,2)+pow($k_waktu,2)+pow($k_jarak,2);
    	
        array_push($si_plus, sqrt($b));
    }
    
    //menghitung separasi solusi ideal negatif (Si-)
    for ($i = 0; $i < $length; $i++) {
    	$k_rating = pow(($a_t_rating[$i]-$a_min_rating),2);
    	$k_ulasan = pow(($a_t_ulasan[$i]-$a_min_ulasan),2);
    	$k_waktu = pow(($a_t_waktu_operasional[$i]-$a_min_waktu_operasional),2);
    	$k_jarak = pow(($a_t_jarak[$i]-$a_plus_jarak),2);
    	
    	$z = sqrt($k_rating+$k_ulasan+$k_waktu+$k_jarak);
    	
        array_push($si_min, $z);
    }
    
    //mencari kedekatan relatif dengan solusi yang optimal
    $ci_plus = array();
    for ($i = 0; $i < $length; $i++) {
        $n_ci_plus = $si_min[$i] / ($si_min[$i]+$si_plus[$i]);
        array_push($ci_plus, $n_ci_plus);
    }
    
    //melakukan ranking dari setiap alternatif
    $cde = array_combine($id, $ci_plus);
    arsort($cde);
    $out = array_values($cde);
    $id_rekomendasi = array_keys($cde);
    
    $list = implode(',', $id_rekomendasi);
    $order_array = 'ORDER BY';
    foreach ($id_rekomendasi as $item) {
        $order_array .= ' id = ' . $item . ' DESC,';
    }
    
    //menampilkan hasil rekomendasi
    $order_array = trim($order_array, ',');
    $query = "SELECT * FROM alternatif_wisata_belanja WHERE id IN ($list) $order_array";
	$result = mysqli_query($con,$query);

	$response = array();
	
	$j = 0;

	while($row = mysqli_fetch_array($result))
	{
		array_push($response,array("id"=>$row[0],"nama_tempat"=>$row[1],"rating"=>$row[2],"ulasan"=>$row[3],"jam_buka"=>$row[4],"jam_tutup"=>$row[5],"lat"=>$row[6],"long"=>$row[7],"status"=>$row[8],"alamat"=>$row[9],"nilai"=>$out[$j]));
		$j++;
	}
	echo json_encode(array("server_response"=>$response));
	
// 	while($row = mysqli_fetch_array($result))
// 	{
// 		array_push($response,array("id"=>$row[0],"nama_tempat"=>$row[1],"rating"=>$row[2],"ulasan"=>$row[3],"jam_buka"=>$row[4],"jam_tutup"=>$row[5],"lat"=>$row[6],"long"=>$row[7],"status"=>$row[8],"alamat"=>$row[9]));
// 		$j++;
// 	}
// 	echo json_encode(array("server_response"=>$response));

    mysqli_close($con);
}
?>