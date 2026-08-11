# Crevellari_FeaturedProduct

[![CI](https://github.com/crevellarivinicius/cvlr-module-featured-product/actions/workflows/ci.yml/badge.svg)](https://github.com/crevellarivinicius/cvlr-module-featured-product/actions/workflows/ci.yml)
![Magento](https://img.shields.io/badge/Magento-2.4.6%20Open%20Source-ee672f)
![PHP](https://img.shields.io/badge/PHP-8.1%20%7C%208.2-777bb4)
![License](https://img.shields.io/badge/license-OSL--3.0-2ea44f)

Módulo para **Magento 2.4.6 Open Source (tema Luma)** que exibe um **produto em destaque na homepage**, com a **quantidade disponível para venda atualizada em tempo real** (sem recarregar a página).


---

## Visão geral

O módulo insere um box como **primeiro elemento do conteúdo principal** da homepage (logo abaixo do cabeçalho e menu), ocupando **toda a largura do content main**, contendo:

- **Título** do produto;
- **Preço** renderizado pelo *pricing render pipeline* nativo (respeita preço especial, regras de exibição de impostos e moeda da store);
- **Imagem base** (image role `image`), servida pelo pipeline de imagens do catálogo (cache/resize nativos);
- **Quantidade disponível para venda** (*salable quantity* do MSI), atualizada periodicamente via Knockout + polling AJAX;
- Clique em qualquer área do box leva à **página do produto**.

Nenhum arquivo do tema é modificado: **tudo vive dentro da pasta do módulo**.

![Box de produto em destaque na homepage Luma](docs/home-desktop.png)

| Estoque baixo | Esgotado | Mobile |
|---|---|---|
| ![Estoque baixo](docs/home-lowstock.png) | ![Esgotado](docs/home-outofstock.png) | ![Mobile](docs/home-mobile.png) |

## Instalação

### Opção A — Composer (recomendada)

Com o zip extraído (ou repositório clonado) em uma pasta local, ex.: `extensions/crevellari/module-featured-product`:

```bash
composer config repositories.crevellari path extensions/crevellari/module-featured-product
composer require crevellari/module-featured-product:1.0.0
bin/magento module:enable Crevellari_FeaturedProduct
bin/magento setup:upgrade
bin/magento setup:di:compile   # production mode
bin/magento cache:flush
```

### Opção B — app/code

```bash
mkdir -p app/code/Crevellari/FeaturedProduct
# copie o conteúdo do módulo para a pasta acima
bin/magento module:enable Crevellari_FeaturedProduct
bin/magento setup:upgrade
bin/magento cache:flush
```

## Configuração (painel admin)

**Stores → Configuration → Catalog → Featured Product**

| Campo | Descrição | Padrão |
|---|---|---|
| Enabled | Liga/desliga o box na homepage | Sim |
| Product SKU | SKU do produto em destaque (digite o SKU, ex. `24-MB01`) | `24-MB01` |
| Stock Refresh Interval (seconds) | Intervalo do polling de estoque (5–300s, validado também no servidor) | 10 |
| Low Stock Threshold | Qtde. igual/abaixo da qual o box exibe o alerta "Últimas unidades!" (0 desativa) | 5 |

Todos os campos têm escopo de **store view** (suporte multi-loja/multi-idioma) e botão *Use system value* (`canRestore`).

## Como funciona a atualização em tempo real

1. O componente Knockout (`view/frontend/web/js/view/stock.js`) faz uma requisição imediata no carregamento e agenda um `setInterval` com o intervalo configurado no admin;
2. Cada ciclo chama `GET /featuredproduct/stock/get` — um controller fino (`HttpGetActionInterface`) que apenas delega ao service contract e formata o JSON;
3. O service (`Api\StockInformationInterface` → `Model\StockInformation`) consulta a salable quantity do MSI (`GetProductSalableQtyInterface`), com fallback para o estoque legado em tipos de produto sem source item;
4. A resposta atualiza os observables (`qty`, `isSalable`, `updatedAt`) e o template Knockout re-renderiza apenas o indicador de estoque — nada mais na página muda.

- A quantidade exibida é a **salable quantity do MSI** (estoque físico − reservas), que é exatamente a "quantidade disponível para venda" — decrementa na hora em que um pedido é feito, sem esperar reindexação;
- O polling **pausa quando a aba está em segundo plano** (Page Visibility API) e atualiza imediatamente quando o visitante volta;
- Em caso de falha de rede, o último valor conhecido permanece na tela com um aviso discreto e o componente continua tentando;
- O SKU **não** trafega no request: o endpoint lê a configuração no servidor, evitando que seja usado para sondar estoque de produtos arbitrários;
- Resposta JSON com `Cache-Control: no-store` (nunca cacheada).

### Bônus: REST API

O mesmo service contract é exposto via WebAPI:

```
GET /rest/V1/featured-product/stock
```

## Arquitetura e decisões técnicas

| Camada | Arquivo | Papel |
|---|---|---|
| Service contract | `Api/StockInformationInterface` + `Model/StockInformation` | Única fonte da regra de negócio de estoque (usada pelo controller, pela webapi e pelo render server-side) |
| DTO | `Api/Data/StockInterface` + `Model/Data/Stock` | Estruturação dos dados trafegados |
| Controller | `Controller/Stock/Get.php` | **Fino**: só HTTP→service→JSON. Não instancia blocos nem contém lógica (MVC respeitado) |
| ViewModel | `ViewModel/FeaturedProduct.php` | Dados do produto para o template (substitui "fat blocks") |
| Block | `Block/FeaturedProduct.php` | Cola de apresentação: mescla config dinâmica no `jsLayout` (mesmo padrão do checkout) e implementa `IdentityInterface` para o Full Page Cache da home ser invalidado quando o produto muda |
| Layout | `view/frontend/layout/cms_index_index.xml` | `referenceContainer`/`referenceBlock`, **block arguments** (view model, título, jsLayout), CSS carregado **somente na homepage** |
| JS | `view/frontend/web/js/view/stock.js` | `uiComponent` **Knockout** carregado via RequireJS apenas onde é usado (sem `default_head_blocks`) |
| Config | `etc/adminhtml/system.xml`, `etc/config.xml`, `etc/acl.xml` | Configuração via admin com ACL própria e defaults |

### Recursos nativos de layout demonstrados

- **Reference block**: declaração do bloco em `referenceContainer name="content"` e configuração via `referenceBlock name="featured.product"`;
- **Block arguments**: `view_model`, `box_title` e `jsLayout` passados por `<arguments>`;
- **jsLayout**: estrutura declarativa do componente no XML, enriquecida em runtime por `Block::getJsLayout()`;
- **Knockout**: componente com observables/computeds e template KO (`view/frontend/web/template/stock.html`);
- **Configurações via admin**: seção própria em Stores → Configuration, com seleção do produto por SKU.

### Design alinhado ao Luma

O box não inventa vocabulário visual: reutiliza os tokens do próprio tema —
cores de mensagem do Luma para os estados de estoque (success `#e5efe5/#006400`,
warning `#fdf0d5/#6f4400`, error `#fae5e5/#e02b27`), CTA no estilo do botão
primário (`#1979c3`, hover `#006bb4`, radius 3px), heading com `font-weight: 300`
como os demais títulos da página, bordas `#ccc` e texto secundário `#757575`.
O resultado parece parte nativa do tema, não um plugin de terceiro.

### Outros cuidados

- `declare(strict_types=1)` e tipagem em todo o código PHP;
- Sem uso de ObjectManager direto, helpers em template ou lógica em controller;
- Estados visuais: normal, estoque baixo, esgotado, erro de rede, animação de pulso quando a quantidade muda, `aria-live` para acessibilidade e suporte a `prefers-reduced-motion`;
- Se o SKU configurado não existir ou o produto estiver desabilitado, o box simplesmente não é renderizado (com log de aviso) — a homepage nunca quebra;
- Traduções `en_US` e `pt_BR` (`i18n/`).

## Testes unitários

```bash
vendor/bin/phpunit -c dev/tests/unit/phpunit.xml.dist \
  app/code/Crevellari/FeaturedProduct/Test/Unit
```

## Compatibilidade

- Magento 2.4.6 Open Source (PHP 8.1/8.2), tema Luma.
