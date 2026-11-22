<?php
declare(strict_types=1);
require __DIR__.'/includes/bootstrap.php';
if(PHP_SAPI!=='cli'){http_response_code(403);exit("Run setup.php from the command line only.\n");}
try{$pdo=new PDO('mysql:host='.DB_HOST.';charset=utf8mb4',DB_USER,DB_PASS,[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]);$sql=file_get_contents(__DIR__.'/database/schema.sql');foreach(array_filter(array_map('trim',explode(';',$sql))) as $statement)$pdo->exec($statement);$pdo->exec('USE '.DB_NAME);$email='admin@crs.test';$q=$pdo->prepare('SELECT id FROM users WHERE email=?');$q->execute([$email]);if(!$q->fetchColumn()){$ins=$pdo->prepare('INSERT INTO users(email,password_hash,role) VALUES(?,?,?)');$ins->execute([$email,password_hash('Admin123!',PASSWORD_DEFAULT),'admin']);}echo "CRS database and sample data are ready.\nAdmin login: admin@crs.test / Admin123!\n";}catch(Throwable $e){fwrite(STDERR,"Setup failed: ".$e->getMessage()."\n");exit(1);}
