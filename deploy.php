<?php

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

function getArrayValue (array $arr, $key)
{
    if (isset($arr[$key])) {
        return $arr[$key];
    }
    throw new \Exception ("Undefined {$key}");
}

function githubKeyIsOK ($githubSecret, $githubSignature, $body)
{
    $hmac = hash_hmac('sha1', $body, $githubSecret);
    $calcSignature = 'sha1='.$hmac;

    return $calcSignature === $githubSignature;
}

function execDeploy()
{
  $dir = __DIR__;
  return shell_exec("cd {$dir} && make deploy");
}

function logToFile($content, $file)
{
    $time = date('d-m-Y H:i:s');
    file_put_contents($file, "[{$time}]:".$content.PHP_EOL, FILE_APPEND);
}

try {
    loadEnv(__DIR__.'/.env');

    $githubSecret = getArrayValue($_ENV, 'GITHUB_SECRET');
    $githubSignature = getArrayValue($_SERVER, 'HTTP_X_HUB_SIGNATURE');
    $body = file_get_contents('php://input');
    
    if (!githubKeyIsOK($githubSecret, $githubSignature, $body)) {
        throw new \Exception ("Local sha1 {$calcLocalSha1} !== {$github_sha1} Github sha1");
    }

    $resDeploy = execDeploy();
    logToFile($resDeploy, 'deploy.log');
} catch (\Exception $e) {
    logToFile($e->getMessage(), 'deploy.log');
}
