<?php
/**
 * Check Blade templates for PHP syntax errors without booting Laravel.
 *
 *   php scripts/lint_blade.php resources/views/admin/listing/book/ezeeBook.blade.php
 *   php scripts/lint_blade.php resources/views/admin          # recurses
 *
 * Approximates what Blade compiles each directive into, then runs php -l over
 * the result. It will not catch template logic errors, but it does catch the
 * expensive class: a malformed expression inside {{ }} or @php, which otherwise
 * only shows up as a 500 in the browser.
 */

function compileBlade(string $source): string
{
    $s = $source;

    // Comments first: their contents are not compiled.
    $s = preg_replace('/\{\{--.*?--\}\}/s', '', $s);

    // Raw and escaped echoes.
    $s = preg_replace('/\{!!(.+?)!!\}/s', '<?php echo (\1); ?>', $s);
    $s = preg_replace('/\{\{(.+?)\}\}/s', '<?php echo (\1); ?>', $s);

    // Inline PHP.
    $s = preg_replace('/@php(.*?)@endphp/s', '<?php \1 ?>', $s);

    // Directive arguments can nest and can sit inline, so match balanced
    // parentheses rather than anchoring to the end of the line — templates use
    // forms like @foreach($x as $y)<li>..</li>@endforeach on one line.
    $arg = '(\\((?:[^()]++|(?1))*\\))';

    $open = [
        'if'      => 'if %s:',
        'elseif'  => 'elseif %s:',
        'unless'  => 'if (!%s):',
        'isset'   => 'if (isset%s):',
        'hasSection'     => 'if (_blade%s):',
        'sectionMissing' => 'if (!_blade%s):',
        'foreach' => 'foreach %s:',
        'for'     => 'for %s:',
        'while'   => 'while %s:',
    ];
    foreach ($open as $name => $tpl) {
        $s = preg_replace_callback(
            '/@' . $name . '\s*' . $arg . '/',
            fn ($m) => '<?php ' . sprintf($tpl, $m[1]) . ' ?>',
            $s
        );
    }

    // @forelse opens a loop whose @empty acts as the else branch.
    $s = preg_replace_callback(
        '/@forelse\s*' . $arg . '/',
        fn ($m) => '<?php if (true): foreach ' . $m[1] . ': ?>',
        $s
    );

    // @empty($x) is a conditional; bare @empty closes a @forelse.
    $s = preg_replace_callback(
        '/@empty\s*' . $arg . '/',
        fn ($m) => '<?php if (empty' . $m[1] . '): ?>',
        $s
    );
    $s = preg_replace('/@empty\b/', '<?php endforeach; else: ?>', $s);

    $closers = [
        '@endif'      => '<?php endif; ?>',
        '@endunless'  => '<?php endif; ?>',
        '@endisset'   => '<?php endif; ?>',
        '@endempty'   => '<?php endif; ?>',
        '@endforeach' => '<?php endforeach; ?>',
        '@endforelse' => '<?php endif; ?>',
        '@endfor'     => '<?php endfor; ?>',
        '@endwhile'   => '<?php endwhile; ?>',
        '@else'       => '<?php else: ?>',
    ];
    $s = str_replace(array_keys($closers), array_values($closers), $s);

    // Directives whose expression only needs syntax-checking.
    $s = preg_replace_callback(
        '/@(?:include|includeIf|extends|section|push|json|method|can|slot)\s*' . $arg . '/',
        fn ($m) => '<?php _blade' . $m[1] . '; ?>',
        $s
    );

    // Bare directives carrying no expression.
    $s = preg_replace('/@(csrf|endsection|endpush|stack|show|endslot|endcan|parent)\b/', '', $s);

    return $s;
}

function lintFile(string $path): bool
{
    $compiled = compileBlade(file_get_contents($path));
    $tmp = tempnam(sys_get_temp_dir(), 'blade') . '.php';
    file_put_contents($tmp, $compiled);

    exec('php -l ' . escapeshellarg($tmp) . ' 2>&1', $out, $code);
    unlink($tmp);

    if ($code !== 0) {
        echo "FAIL  $path\n";
        foreach ($out as $line) {
            if (stripos($line, 'No syntax errors') === false) {
                echo '      ' . str_replace($tmp, basename($path), $line) . "\n";
            }
        }
        return false;
    }
    return true;
}

$targets = array_slice($argv, 1);
if (!$targets) {
    echo "usage: php scripts/lint_blade.php <file-or-directory>...\n";
    exit(1);
}

$files = [];
foreach ($targets as $t) {
    if (is_dir($t)) {
        $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($t));
        foreach ($it as $f) {
            if (substr($f->getFilename(), -10) === '.blade.php') {
                $files[] = $f->getPathname();
            }
        }
    } elseif (is_file($t)) {
        $files[] = $t;
    }
}

sort($files);
$failed = 0;
foreach ($files as $f) {
    if (!lintFile($f)) {
        $failed++;
    }
}

printf("\n%d file(s) checked, %d with syntax errors\n", count($files), $failed);
exit($failed === 0 ? 0 : 1);
