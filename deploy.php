<?php

function calcSha1($body, $my_key)
{
    $hmac = hash_hmac('sha1', $body, $my_key);
    return 'sha1='.$hmac;
}

function loadEnv($path)
{
    $content = file_get_contents($path);

    if ($content === false) {
        throw new \Exception('.env file not found');
    }

    $lines = explode(PHP_EOL, $content);
    foreach ($lines as $line) {
      if ($line !== '') {
        $keyVal = explode('=', $line);
        $key = $keyVal[0];
        $value = $keyVal[1] ?? '';
        $_ENV[$key] = $value;
      }
    }
    
}

$time = date('d-m-Y H:i:s');

try {
    loadEnv(__DIR__.'/.env');

    if (!isset($_ENV['GITHUB_SECRET'])) {
      throw new \Exception ('Undefined github secret key');
    }

    if (!isset($_SERVER['HTTP_X_HUB_SIGNATURE'])) {
      throw new \Exception ('Undefined HTTP_X_HUB_SIGNATURE');
    }

    $githubSecret = $_ENV['GITHUB_SECRET'];
    $github_sha1 = $_SERVER['HTTP_X_HUB_SIGNATURE'];
    $body = file_get_contents('php://input');
    $calcLocalSha1 = calcSha1($body, $githubSecret);
    
    if ($calcLocalSha1 !== $github_sha1) {
        throw new \Exception ("Local sha1 {$calcLocalSha1} !== {$github_sha1} Github sha1");
    }

    $dir = __DIR__;
    $res = shell_exec("cd {$dir} && make deploy");
    file_put_contents('deploy.log', "[{$time}]:".$res.PHP_EOL, FILE_APPEND);

} catch (\Exception $e) {

    file_put_contents('deploy.log', "[{$time}]:".$e->getMessage().PHP_EOL, FILE_APPEND);

} finally {

    file_put_contents('post.log', json_encode($_POST));

}

