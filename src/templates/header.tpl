{% set template_dir = apps.currentApp('basename') %}
<!DOCTYPE html>
<html lang="ja"{% if header.class is defined %} class="{{ header.class is iterable ? header.class|join(' ') : header.class }}"{% endif %} id="{{ header.id }}">
  {% if header.id != 'signin' %}
    <noscript>
      <meta http-equiv="refresh" content="1; URL=?logout">
    </noscript>
  {% endif %}
  <meta charset="utf-8">
  <meta name="robots" content="noindex,nofollow">
  <title>Tak-Me System: {{ header.title }}</title>
  <link rel="stylesheet" href="{{ config.global.assets_path }}style/default.css">
  {% set template = template_dir ~ "/more_links.tpl" %}
  {% if apps.view.exists(template) %}
    {% include template %}
  {% endif %}
  {{ links|raw }}
  <script src="{{ config.global.assets_path }}script/common.js" charset="utf-8"></script>
  <script src="{{ config.global.assets_path }}script/init.js" charset="utf-8"></script>
  {% set template = template_dir ~ "/html_header.tpl" %}
  {% if apps.view.exists(template) %}
    {% include template %}
  {% endif %}
  {{ head|raw }}
  <script src="{{ config.global.assets_path }}script/mobile.js"></script>
