<?php

function get_source(ReflectionMethod $method)
{
    $file = $method->getFileName();
    $startLine = $method->getStartLine() - 1;
    $endLine = $method->getEndLine();

    $source = file($file);
    $source = implode('', array_slice($source, 0, count($source)));
    // $source = preg_split("/(\n|\r\n|\r)/", $source);
    $source = preg_split('/'.PHP_EOL.'/', $source);

    $body = '';
    for ($i = $startLine; $i < $endLine; $i++) {
        $body .= "{$source[$i]}\n";
    }

    return $body;
}

function get_fields($gateway, $request)
{
    $exclude = ['amount', 'currency', 'description', 'transactionId', 'transactionReference', 'cardReference', 'returnUrl', 'cancelUrl', 'notifyUrl', 'issuer', 'money', 'items', 'clientIp', 'testMode', 'card', 'amountInteger', 'response', 'parameters', 'data'];
    $exclude = array_merge($exclude, array_keys($gateway->getDefaultParameters()));

    $class = new ReflectionClass($request);

    $methods = array_filter($class->getMethods(ReflectionMethod::IS_PUBLIC), function (ReflectionMethod $method) {
        return $method->getNumberOfParameters() === 0 && strpos($method->getName(), 'get') !== false;
    });

    $methods = array_filter($methods, function (ReflectionMethod $method) use ($exclude) {
        $source = get_source($method);

        foreach ($exclude as $field) {
            foreach ([$method->getName(), $source] as $content) {
                if ((bool) preg_match('/get'.$field.'/i', $content) !== false) {
                    return false;
                }
            }
        }

        return true;
    });

    $fields = array_map(function (ReflectionMethod $method) use ($request) {
        $name = preg_replace('/^get/', '', $method->getName());

        return ['name' => $name, 'value' => $method->invoke($request)];
    }, $methods);

    return $fields;
}
