<?php declare(strict_types=1);

namespace Ssdk\Oalog\Formatter;

use Monolog\Formatter\LineFormatter as BaseLineFormatter;
use Monolog\Utils;

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
    public function format(array $record): string
    {
        if ($record['datetime'] instanceof \DateTimeInterface) {
            $record['created_at'] = $record['datetime']->format("Y-m-d H:i:s.u");
            unset($record['datetime']);
            $record['datetime'] = $record['created_at'];
            unset($record['created_at']);
        }
        
        $record['level'] = strtolower($record['level_name']);
        $record['context']['extra'] = $record['extra'];
        $record['context']['level_name'] = $record['level_name'];
        $record['context']['level_no'] = $record['level_no'];
        $record['context']['channel'] = $record['channel'];
        unset($record['extra']);
        unset($record['level_name']);
        unset($record['level_no']);
        unset($record['channel']);
        
        $vars = $this->normalize($record);
        $output = $this->format;
        
 /*
        foreach ($vars['extra'] as $var => $val) {
            if (false !== strpos($output, '%extra.'.$var.'%')) {
                $output = str_replace('%extra.'.$var.'%', $this->stringify($val), $output);
                unset($vars['extra'][$var]);
            }
        }
*/
        
        foreach ($vars['context'] as $var => $val) {
            if (false !== strpos($output, '%context.'.$var.'%')) {
                $output = str_replace('%context.'.$var.'%', $this->stringify($val), $output);
                unset($vars['context'][$var]);
            }
        }
//        $vars['extra'] = json_encode($vars['extra']);
        $vars['context'] = json_encode($vars['context'], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        
        
        if ($this->ignoreEmptyContextAndExtra) {
            if (empty($vars['context'])) {
                unset($vars['context']);
                $output = str_replace('%context%', '', $output);
            }
            
//             if (empty($vars['extra'])) {
//                 unset($vars['extra']);
//                 $output = str_replace('%extra%', '', $output);
//             }
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
