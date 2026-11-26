// Confirmação antes de ações destrutivas: <form data-confirm="mensagem">
document.addEventListener('submit', function (ev) {
  var msg = ev.target.getAttribute('data-confirm');
  if (msg && !window.confirm(msg)) ev.preventDefault();
});
// Pré-visualização da imagem no formulário de produto
document.addEventListener('change', function (ev) {
  if (ev.target.id !== 'image' || !ev.target.files[0]) return;
  var box = document.getElementById('preview');
  if (box) { box.src = URL.createObjectURL(ev.target.files[0]); box.classList.remove('d-none'); }
});
