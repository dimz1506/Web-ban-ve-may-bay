<?php
         if(!function_exists('db')){
                  require_once dirname(__DIR__).'/config.php';
         }
         require_login(['ADMIN']);
         $pdo=db();

         function flash_ok($m) {
                  flash_set('ok', $m);
         }
         function flash_err($m){
                  flash_set('err', $m);
         }

         //handle POST
         if($_SERVER['REQUEST_METHOD'] == 'POST'){
                  require_post_csrf();
                  $action = $_POST['action'] ?? '';
                  try{
                           if($action === 'create' || $action === 'update'){
                                    $id = (int)($_POST['id'] ?? 0);
                                    $ma = trim($_POST['ma'] ?? '');
                                    $ten = trim($_POST['ten'] ?? '');
                                    if($ma === '' || $ten === '')
                                    {
                                             throw new RuntimeException("Thieu du lieu.");
                                    }
                                    if($action === 'create'){
                                             $pdo -> prepare("INSERT INTO hang_ghe(ma, ten) VALUES (?,?)") -> execute ([$ma, $ten]);
                                             flash_ok("Da them hang ghe.");
                                    }
                                    else{
                                             $pdo -> prepare("UPDATE hang_ghe SET ma=? , ten=? WHERE id=?") ->execute([$ma, $ten]);
                                             flash_ok("Da cap nhat hang ghe.");
                                    }      
                           }
                           elseif($action === 'delete'){
                                    $id = (int)($_POST['id'] ?? 0);
                                    $pdo -> prepare("DELETE FROM hang_ghe WHERE id=?") -> execute([$id]);
                                    flash_ok("Da xoa hang ghe.");
                           }
                  
                  }
                  catch(Throwable $e){
                           flash_err($e -> getMessage());
                  }
                  redirect("index.php?p=classes");
         }

         //danh sach
         $rows = $pdo -> query("SELECT * FROM hang_ghe ORDER BY id") -> fetchAll();

         //edit
         $edit_id = (int)($_GET['edit'] ?? 0);
         $edit_row = null;
         if($edit_id){
                  foreach($rows as $r){
                           if((int) $r['id'] === $edit_id) {
                                    $edit_row = $r;
                           }
                  }
         }
         include dirname(__DIR__) .'/../templates/classes_view.php';
?>