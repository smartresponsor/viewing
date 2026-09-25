<?php

declare(strict_types=1);

require dirname(__DIR__).'/vendor/autoload.php';

use App\Viewing\Service\ViewTrafficClassifier;
use Symfony\Component\HttpFoundation\Request;

$cases = [
    'empty-ua' => [new ViewTrafficClassifier(['/bot/i']), Request::create('/a', server: ['HTTP_USER_AGENT' => ''])],
    'bot-first' => [new ViewTrafficClassifier(['/bot/i']), Request::create('/b', server: ['HTTP_USER_AGENT' => 'ExampleBot/1.0'])],
    'bot-later' => [new ViewTrafficClassifier(['/crawler/i', '/bot/i']), Request::create('/c', server: ['HTTP_USER_AGENT' => 'ExampleBot/1.0'])],
    'human' => [new ViewTrafficClassifier(['/bot/i']), Request::create('/d', server: ['HTTP_USER_AGENT' => 'Mozilla/5.0', 'HTTP_SEC_FETCH_SITE' => 'same-origin', 'HTTP_SEC_FETCH_MODE' => 'navigate'])],
    'bad-site' => [new ViewTrafficClassifier(['/bot/i']), Request::create('/e', server: ['HTTP_USER_AGENT' => 'Mozilla/5.0', 'HTTP_SEC_FETCH_SITE' => 'invalid', 'HTTP_SEC_FETCH_MODE' => 'navigate'])],
    'bad-mode' => [new ViewTrafficClassifier(['/bot/i']), Request::create('/f', server: ['HTTP_USER_AGENT' => 'Mozilla/5.0', 'HTTP_SEC_FETCH_SITE' => 'same-origin', 'HTTP_SEC_FETCH_MODE' => 'invalid'])],
    'no-patterns' => [new ViewTrafficClassifier([]), Request::create('/g', server: ['HTTP_USER_AGENT' => 'Mozilla/5.0'])],
];

$file = realpath(dirname(__DIR__).'/src/Service/ViewTrafficClassifier.php');
foreach ($cases as $label => [$classifier, $request]) {
    xdebug_start_code_coverage(XDEBUG_CC_UNUSED | XDEBUG_CC_DEAD_CODE | XDEBUG_CC_BRANCH_CHECK);
    $classifier->classify($request);
    $entry = xdebug_get_code_coverage()[$file] ?? [];
    xdebug_stop_code_coverage(true);

    echo "CASE: ".$label.PHP_EOL;
    foreach (($entry['functions'] ?? []) as $name => $data) {
        if (!str_contains($name, 'ViewTrafficClassifier->')) {
            continue;
        }
        $hits = [];
        foreach (($data['paths'] ?? []) as $index => $path) {
            if (($path['hit'] ?? 0) > 0) {
                $hits[] = $index;
            }
        }
        echo "  ".$name." paths=".json_encode($hits).PHP_EOL;
        foreach (($data['branches'] ?? []) as $index => $branch) {
            if (($branch['hit'] ?? 0) === 0) {
                echo sprintf("    MISS branch=%s lines=%s-%s", (string)$index, $branch['line_start'] ?? '?', $branch['line_end'] ?? '?').PHP_EOL;
            }
        }
    }
}
