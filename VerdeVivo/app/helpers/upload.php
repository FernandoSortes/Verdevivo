<?php
/**
 * Valida e salva a imagem de um produto.
 * Retorna o nome do arquivo salvo, ou null se nenhum arquivo foi enviado.
 * Lança RuntimeException se o arquivo for inválido.
 */
function handle_image_upload(string $field = 'image'): ?string
{
    if (empty($_FILES[$field]) || $_FILES[$field]['error'] === UPLOAD_ERR_NO_FILE) return null;
    $f = $_FILES[$field];
    if ($f['error'] !== UPLOAD_ERR_OK) throw new RuntimeException('Falha no envio da imagem.');
    if ($f['size'] > 2 * 1024 * 1024) throw new RuntimeException('A imagem deve ter no máximo 2 MB.');
    if (!is_uploaded_file($f['tmp_name'])) throw new RuntimeException('Upload inválido.');

    $mime = (new finfo(FILEINFO_MIME_TYPE))->file($f['tmp_name']);   // tipo real, não o enviado pelo navegador
    $allowed = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
    if (!isset($allowed[$mime]) || @getimagesize($f['tmp_name']) === false) {
        throw new RuntimeException('Formato inválido. Envie JPG, PNG ou WEBP.');
    }
    $name = bin2hex(random_bytes(10)) . '.' . $allowed[$mime];       // nome gerado pelo servidor
    $dir = ROOT . '/assets/imagens/produtos/';
    if (!move_uploaded_file($f['tmp_name'], $dir . $name)) throw new RuntimeException('Não foi possível salvar a imagem.');
    return $name;
}
function delete_image(?string $name): void
{
    if ($name) {
        $uso = db()->prepare('SELECT COUNT(*) FROM products WHERE image = ?');
        $uso->execute([basename($name)]);
        if ((int)$uso->fetchColumn() > 0) return;   // outro produto ainda usa este arquivo
        $p = ROOT . '/assets/imagens/produtos/' . basename($name); if (is_file($p)) @unlink($p); }
}
