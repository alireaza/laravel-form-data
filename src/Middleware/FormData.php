<?php

namespace AliReaza\Laravel\Request\Middleware;

use AliReaza\Component\HttpFoundation\Request\FormData as FormDataHandler;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Symfony\Component\HttpFoundation\Response;

class FormData
{
    private array $disallowMethods = [
        Request::METHOD_GET,
        Request::METHOD_HEAD,
        Request::METHOD_POST,
    ];

    public function handle(Request $request, Closure $next): Response
    {
        if (!in_array($request->getRealMethod(), $this->disallowMethods)) {
            $headers = $request->headers;
            $contentType = $headers->get('content-type');

            if (preg_match('/multipart\/form-data/', $contentType)) {
                $content = $request->getContent();

                $static = new FormDataHandler($content);

                $request->request->add($static->inputs);

                $request->files->add($static->files);

                $files = $this->handleFiles($request->files->all());
                $request->files->replace($files);
            }
        }

        return $next($request);
    }

    private function handleFiles(array $files): array
    {
        $result = [];

        foreach ($files as $key => $file) {
            $result[$key] = is_array($file) ? $this->handleFiles($file) : UploadedFile::createFromBase($file, true);
        }

        return $result;
    }
}
