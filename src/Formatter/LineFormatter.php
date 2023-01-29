<?php declare(strict_types=1);

namespace Ssdk\Oalog\Formatter;

use Monolog\Formatter\LineFormatter as BaseLineFormatter;
use Monolog\Utils;
use Monolog\LogRecord;

class LineFormatter extends BaseLineFormatter
{
    public const SIMPLE_FORMAT = "time:%datetime%, level:%level%, message:%message%, context:%context%\n";
    
    public function __construct(?string $format = null, ?string $dateFormat = null, bool $allowInlineLineBreaks = false, bool $ignoreEmptyContextAndExtra = false)
    {
        parent::__construct($format, $dateFormat, $allowInlineLineBreaks, $ignoreEmptyContextAndExtra);
    }
    
    /**
     * {@inheritdoc}
     */
    // public function format(array $record): string
    public function format(LogRecord $record2): string
    {
        $record = $record2->toArray();
        
        $record['datetime'] = $record['datetime']->format('Y-m-d H:i:s.u');
        $record['level'] = strtolower($record['extra']['level_name']);
        $record['context']['extra'] = $record['extra'];
        $record['context']['level_name'] = $record['extra']['level_name'];
        $record['context']['level_no'] = $record['extra']['level_no'];
        $record['context']['channel'] = $record['channel'];
        unset($record['context']['extra']['level_name']);
        unset($record['context']['extra']['level_no']);
        unset($record['extra']);
        unset($record['channel']);
        
        $vars = $this->normalize($record);
        $output = $this->format;
        
        foreach ($vars['context'] as $var => $val) {
            if (false !== strpos($output, '%context.'.$var.'%')) {
                $output = str_replace('%context.'.$var.'%', $this->stringify($val), $output);
                unset($vars['context'][$var]);
            }
        }
        $vars['context'] = json_encode($vars['context'], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        
        if ($this->ignoreEmptyContextAndExtra) {
            if (empty($vars['context'])) {
                unset($vars['context']);
                $output = str_replace('%context%', '', $output);
            }
        }
        
        foreach ($vars as $var => $val) {
            if (false !== strpos($output, '%'.$var.'%')) {
                $output = str_replace('%'.$var.'%', $this->stringify($val), $output);
            }
        }
        
        // remove leftover %extra.xxx% and %context.xxx% if any
        if (false !== strpos($output, '%')) {
            $output = preg_replace('/%(?:extra|context)\..+?%/', '', $output);
        }
        
        return $output;
    }
    
    private function formatException(\Throwable $e): string
    {
        $str = '[object] (' . Utils::getClass($e) . '(code: ' . $e->getCode();
        if ($e instanceof \SoapFault) {
            if (isset($e->faultcode)) {
                $str .= ' faultcode: ' . $e->faultcode;
            }
            
            if (isset($e->faultactor)) {
                $str .= ' faultactor: ' . $e->faultactor;
            }
            
            if (isset($e->detail) && (is_string($e->detail) || is_object($e->detail) || is_array($e->detail))) {
                $str .= ' detail: ' . (is_string($e->detail) ? $e->detail : reset($e->detail));
            }
        }
        $str .= '): ' . $e->getMessage() . ' at ' . $e->getFile() . ':' . $e->getLine() . ')';
        
        if ($this->includeStacktraces) {
            $str .= "\n[stacktrace]\n" . $e->getTraceAsString() . "\n";
        }
        
        return $str;
    }
}
