<?php

namespace Ejz\LogFormatter;

use Monolog\Logger;
use Monolog\Formatter\NormalizerFormatter;

class LogFormatter extends NormalizerFormatter {
    public function __construct($dateFormat = null) {
        parent::__construct($dateFormat);
    }
    public function format(array $record) {
        if (isset($record['context']['file']) and isset($record['context']['line'])) {
            $line = $record['context']['line'];
            $file = $record['context']['file'];
            $from = "{$file}:{$line}";
            if (strpos($file, ROOT) === 0) $file = ltrim(substr($file, strlen(ROOT)), '/');
            $record['message'] .= sprintf(
                " @ %s:%s",
                $file,
                $line
            );
        }
        if ($record['level'] >= Logger::ALERT)
            $record['message'] .= $this->alertBacktrace(isset($from) ? $from : '');
        elseif ($record['level'] >= Logger::WARNING)
            $record['message'] .= $this->warningBacktrace(isset($from) ? $from : '');
        $output = "";
        $output .= sprintf(
            "[%s] [%s] %s",
            $record['datetime']->format($this->dateFormat),
            $record['level_name'],
            (string)($record['message'])
        );
        foreach ($record['context'] as $k => $v)
            if (!in_array($k, array('file', 'line', 'code', 'message', 'context')))
                $output .= sprintf(" | %s => %s", $k, $this->formatValue($v));
        foreach ($record['extra'] as $k => $v)
            $output .= sprintf(" | %s => %s", $k, $this->formatValue($v));
        $output .= "\n";
        return $output;
    }
    private function formatValue($value, $level = 0) {
        if ($level > 2) return '';
        if (is_string($value)) return trim($value);
        if (is_numeric($value)) return $value;
        if (is_bool($value)) return $value ? '#true' : '#false';
        if (is_null($value)) return '#null';
        if (is_array($value) and !is_assoc($value)) {
            $return = array();
            foreach ($value as $v)
                $return[] = $this->formatValue($v, $level + 1);
            return sprintf("#array(%s)", implode(', ', $return));
        }
        if (is_array($value)) {
            $return = array();
            foreach ($value as $k => $v)
                $return[] = sprintf("%s => %s", $k, $this->formatValue($v, $level + 1));
            return sprintf("#array(%s)", implode(', ', $return));
        }
        if (is_object($value) and method_exists($value, '__toString'))
            return trim($value . '');
        if (is_object($value)) return '#' . get_class($value);
        if (is_resource($value)) return '#resource';
        return '#unknown';
    }
    private function alertBacktrace($from) {
        return $this->echoBacktrace('alert', $from);
    }
    private function warningBacktrace($from) {
        return $this->echoBacktrace('warning', $from);
    }
    // type = alert, warning
    // from = "file:line"
    private function echoBacktrace($type, $from) {
        $traces = debug_backtrace();
        $echo = array();
        $_from = $from;
        foreach ($traces as $trace)
            if ($_from and isset($trace['file']) and isset($trace['line']) and $_from === "{$trace['file']}:{$trace['line']}")
                $_from = false;
        if ($_from) $from = false;
        foreach ($traces as $trace) {
            if (!isset($trace['file']) or !isset($trace['line']))
                continue;
            if ($from and $from === "{$trace['file']}:{$trace['line']}") {
                $from = false;
                if ($type === 'warning') continue;
            }
            if ($from) continue;
            $file = $trace['file'];
            if (strpos($file, ROOT) === 0) $file = ltrim(substr($file, strlen(ROOT)), '/');
            $str = "{$file}:{$trace['line']}";
            if ($type === 'alert' and (!isset($trace['function']) or !isset($trace['args'])))
                $str .= "@{$trace['function']}(" . $this->formatValue($trace['args']) . ')';
            $echo[] = $str;
        }
        return ($echo ? ' | ' : '') . implode(' | ', $echo);
    }
}
