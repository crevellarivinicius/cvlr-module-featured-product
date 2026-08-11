# Changelog

Todas as mudanças relevantes deste módulo são documentadas neste arquivo,
seguindo o formato do [Keep a Changelog](https://keepachangelog.com/pt-BR/1.1.0/)
e o [Versionamento Semântico](https://semver.org/lang/pt-BR/).

## [1.0.0] - 2026-08-11

### Adicionado

- Box de produto em destaque como primeiro elemento do conteúdo principal da homepage (tema Luma), com título, preço, imagem base e link para a página do produto.
- Quantidade disponível para venda em tempo real (salable quantity do MSI, com fallback para o estoque legado), atualizada via componente Knockout com polling configurável.
- Endpoint JSON para o polling (`GET /featuredproduct/stock/get`) com **requisições condicionais** (`ETag`/`304 Not Modified`), endpoint REST (`GET /rest/V1/featured-product/stock`) e query GraphQL (`featuredProductStock`), todos servidos pelo mesmo service contract.
- Validação do SKU no salvamento da configuração (backend model): um SKU inexistente é rejeitado com mensagem amigável no admin.
- Template alternativo para **Hyvä** (Alpine.js + Tailwind CSS) em `view/frontend/templates/hyva/`.
- ADR documentando a escolha de polling condicional em vez de SSE/WebSocket (`docs/adr/`).
- Configuração via painel admin (Stores > Configuration > Catalog > Featured Product): habilitar/desabilitar, SKU do produto, intervalo de atualização e limite de estoque baixo, com escopo de store view e ACL própria.
- Estados visuais de estoque baixo, esgotado e erro de rede, seguindo as cores de mensagem do tema Luma.
- Invalidação automática do Full Page Cache da homepage quando o produto em destaque é alterado (`IdentityInterface`).
- Traduções `en_US` e `pt_BR`.
- Testes unitários do service de estoque e da configuração, e testes de integração do service (com fixture de produto) e do endpoint.
