<?php

declare(strict_types=1);

/**
 * ============================================================================
 * HRTech Core — Script de Empacotamento para Reuso (Build de Artefato Distribuível)
 * ============================================================================
 * 
 * Transforma o código-fonte em src/ e seus artefatos em um componente distribuível
 * reutilizável (Arquivo .phar / Pacote Distribuível) formalizado via composer.json.
 */

echo "\n📦 ========================================================\n";
echo "    INICIANDO O EMPACOTAMENTO DO COMPONENTE DE REUSO\n";
echo " ========================================================\n\n";

$baseDir    = __DIR__;
$srcDir     = $baseDir . '/src';
$distDir    = $baseDir . '/dist';
$pharFile   = $distDir . '/hrtech-core.phar';
$zipFile    = $distDir . '/hrtech-core-package.zip';

if (!is_dir($distDir)) {
    mkdir($distDir, 0777, true);
}

// 1. Validação dos metadados (composer.json)
$composerJson = $baseDir . '/composer.json';
if (!file_exists($composerJson)) {
    echo "❌ Erro: Manifesto composer.json não encontrado!\n";
    exit(1);
}

$metadata = json_decode(file_get_contents($composerJson), true);
echo "ℹ️  Pacote: " . ($metadata['name'] ?? 'hrtech/core-patterns') . "\n";
echo "ℹ️  Versão: " . ($metadata['version'] ?? '1.0.0') . "\n";
echo "ℹ️  Tipo:   " . ($metadata['type'] ?? 'library') . "\n";
echo "ℹ️  Licença:" . ($metadata['license'] ?? 'MIT') . "\n\n";

// 2. Geração do Pacote Zip Distribuível
if (class_exists('ZipArchive')) {
    echo "⚙️  Empacotando fontes e metadados via ZipArchive...\n";
    $zip = new ZipArchive();
    if ($zip->open($zipFile, ZipArchive::CREATE | ZipArchive::OVERWRITE) === true) {
        $zip->addFile($composerJson, 'composer.json');
        
        $files = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($srcDir, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::LEAVES_ONLY
        );

        foreach ($files as $name => $file) {
            if (!$file->isDir()) {
                $filePath     = $file->getRealPath();
                $relativePath = 'src/' . substr($filePath, strlen($srcDir) + 1);
                $zip->addFile($filePath, $relativePath);
            }
        }
        $zip->close();
        echo "✅ Artefato Zip Distribuível gerado: dist/hrtech-core-package.zip\n";
    }
}

// 3. Geração do Arquivo PHAR Executável (se permitido no ambiente)
if (!ini_get('phar.readonly')) {
    echo "⚙️  Empacotando componente executável em formato PHAR...\n";
    if (file_exists($pharFile)) {
        unlink($pharFile);
    }
    try {
        $phar = new Phar($pharFile);
        $phar->startBuffering();
        $phar->buildFromDirectory($srcDir);
        $stub = "<?php Phar::mapPhar('hrtech-core.phar'); require 'phar://hrtech-core.phar/Autoloader.php'; __HALT_COMPILER();";
        $phar->setStub($stub);
        $phar->stopBuffering();
        echo "✅ Artefato PHAR gerado com sucesso: dist/hrtech-core.phar\n";
    } catch (Exception $e) {
        echo "⚠️  Aviso PHAR: " . $e->getMessage() . "\n";
    }
} else {
    echo "ℹ️  Nota: phar.readonly ativo no php.ini (artefato distribuível mantido em dist/ e Zip).\n";
}

echo "\n🎉 EMPACOTAMENTO CONCLUÍDO COM SUCESSO!\n";
echo "   O componente de reuso está pronto para distribuição em dist/\n\n";
exit(0);
