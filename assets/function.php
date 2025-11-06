<?php
class klass_air{
    function koneksi() {
        $koneksi=mysqli_connect("localhost", "root", "", "db_monitoring2");
        if ($koneksi) {
            // echo "berhasil";
        }else{
            echo "no";
        }
        return $koneksi;
    }

    function dt_user($sesi_user) 
    {
        $q=mysqli_query($this->koneksi(),"SELECT nama,alamat,level FROM user1 WHERE username='$sesi_user'");
        $d=mysqli_fetch_row($q);
        return $d;
    }
}

?>