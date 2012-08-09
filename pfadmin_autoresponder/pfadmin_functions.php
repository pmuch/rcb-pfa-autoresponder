<?php
function removeempty($str) {
  $tmp=array();
  $inp=explode(',',$str);
  foreach($inp as $key => $value) {
    if($value == "") {
      unset($inp[$key]);
    }
  }
  $tmp = array_values($inp);
//  $res = implode(',',$tmp);
  return implode(',',$tmp);
}

function packarray($inp) {
  foreach($inp as $key => $value) {
     if($value == "") {
           unset($inp[$key]);
     }
  }
  return array_values($inp);

}

function removefromalias($db,$alias,$konto){
	$result = true;
        $res = $db->query("select address, goto from alias where address = '$alias'");
        if ($db->affected_rows($res)){
                $row =$db->fetch_assoc($res);
                $aliasy = explode(',',$row["goto"]);
                $newalias = array();
                for ($i=0;$i<count($aliasy);$i++)
                        if (strcmp($aliasy[$i], $konto))
                                $newalias[]=$aliasy[$i];
                if (count($newalias)){
                        $newgoto = implode(',',array_unique($newalias));
                        $db->query("update alias set goto='$newgoto' where address = '$alias'");
			if(!$db->affected_rows($res) == 1)
				$result = false;
                }
                else
                        $db->query("delete from alias where address = '$alias'");
			if(!$db->affected_rows($res) == 1)
				$result = false;
        }
	return $result;
}


function addtoalias($db, $alias, $konto) {
	$result = true;
        $res = $db->query("select address, goto from alias where address = '$alias'");
        $aliasy = array();
        $alreadyincluded =0;
        if ($db->affected_rows($res)){
                $row =$db->fetch_assoc($res);
                $alreadyincluded = (strpos($row["goto"],$konto));
                if (! $alreadyincluded)
                        $aliasy = explode(',',$row["goto"]);
        }
        if (!$alreadyincluded) {
                $aliasy[]=$konto;
                $newgoto =implode(',',array_unique($aliasy));
                $db->query("insert into alias(address, goto) values ('$alias', '$newgoto') on duplicate key update goto='$newgoto'");
		if(!$db->affected_rows($res) == 1)
			$result = false;
        }
	return $result;
}

?>
