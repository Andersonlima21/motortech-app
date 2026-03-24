<?php

namespace App\Logging;

use Monolog\Formatter\JsonFormatter as BaseJsonFormatter;
use Monolog\LogRecord;

class JsonFormatter extends BaseJsonFormatter
{
    /**
     * Formata o log record com campos extras para observabilidade.
     */
    public function format(LogRecord $record): string
    {
        $data = [
            'timestamp'      => $record->datetime->format('Y-m-d\TH:i:s.uP'),
            'level'          => $record->level->getName(),
            'message'        => $record->message,
            'channel'        => $record->channel,
            'correlation_id' => $record->extra['correlation_id'] ?? request()?->header('X-Request-Id') ?? '-',
            'request_method' => request()?->method() ?? '-',
            'request_path'   => request()?->path() ?? '-',
            'user_id'        => auth()->id() ?? '-',
            'context'        => $record->context,
        ];

        return json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . "\n";
    }
}
