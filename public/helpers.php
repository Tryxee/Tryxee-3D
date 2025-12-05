<?php
/**
 * Affiche proprement une variable (html-safe) — usage rapide comme print() en Python.
 *
 * @param mixed  $var    La variable à afficher
 * @param string $label  (optionnel) un texte d'entête
 * @param bool   $halt   (optionnel) si true => arrêt (die) après affichage
 * @param bool   $return (optionnel) si true => retourne la string au lieu d'afficher
 * @return void|string
 */
function p($var, string $label = '', bool $halt = false, bool $return = false) {
    // detect CLI
    $isCli = (php_sapi_name() === 'cli' || defined('STDIN'));

    if ($isCli) {
        // CLI: simple var_export with label
        $out = '';
        if ($label !== '') $out .= "=== {$label} ===\n";
        $out .= var_export($var, true) . "\n";
        if ($return) return $out;
        echo $out;
        if ($halt) exit;
        return;
    }

    // Web: pretty HTML output
    // Build HTML safely (escape)
    $html = '<div style="
        font-family:Menlo, Monaco, monospace;
        background:#0b1116;color:#dff7fb;
        border-radius:10px;padding:12px 14px;margin:12px 0;
        box-shadow:0 10px 30px rgba(2,6,10,0.6);
        overflow:auto;
        max-width:100%;
    ">';
    if ($label !== '') {
        $html .= '<div style="font-weight:700;margin-bottom:6px;color:#bfeeff">' . htmlspecialchars($label, ENT_QUOTES|ENT_SUBSTITUTE) . '</div>';
    }

    // For arrays/objects show var_export-like readable output
    if (is_array($var) || is_object($var)) {
        $dump = var_export($var, true);
        $html .= '<pre style="white-space:pre-wrap;margin:0;">' . htmlspecialchars($dump, ENT_QUOTES|ENT_SUBSTITUTE) . '</pre>';
    } else {
        // scalar: show value and type
        $type = gettype($var);
        if ($var === null) {
            $val = 'null';
        } elseif ($var === '') {
            $val = "''";
        } elseif (is_bool($var)) {
            $val = $var ? 'true' : 'false';
        } else {
            $val = (string)$var;
        }
        $html .= '<div style="font-size:0.95rem;"><strong style="color:#9ff">' . htmlspecialchars($type, ENT_QUOTES|ENT_SUBSTITUTE) . '</strong> : '
              . '<span style="color:#fff">' . htmlspecialchars($val, ENT_QUOTES|ENT_SUBSTITUTE) . '</span></div>';
    }

    $html .= '</div>';

    if ($return) return $html;
    echo $html;

    if ($halt) exit;
}

/**
 * Alias rapide qui dump + die (pratique pour debug rapide)
 */
function pd($var, string $label = '') {
    p($var, $label, true, false);
}

/**
 * Retourne string (non echo) — utile pour logs ou templates
 */
function pr($var, string $label = ''): string {
    return p($var, $label, false, true);
}


/**
 * Affiche le contenu complet de la session ($_SESSION)
 * dans un format lisible HTML.
 */
function debug_session(): void {
    echo '<pre style="
        background: rgba(0,0,0,0.85); 
        color: #4dd0e1; 
        padding: 16px; 
        border-radius: 12px; 
        font-family: monospace;
        font-size: 0.9rem;
        overflow-x: auto;
        max-width: 100%;
        box-shadow: 0 4px 12px rgba(0,0,0,0.4);
    ">';
    echo "=== SESSION DEBUG ===\n\n";
    print_r($_SESSION);
    echo "\n==================";
    echo '</pre>';
}