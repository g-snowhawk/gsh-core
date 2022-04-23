{% if not apps.isAjax %}

{% set blocks = {} %}
{% if block('head') is defined %}
  {% set blocks = {'head': block('head')}|merge(blocks) %}
{% endif %}
{% if block('links') is defined %}
  {% set blocks = {'links': block('links')}|merge(blocks) %}
{% endif %}
{% include 'header.tpl' with blocks %}

{% if messages is not empty %}
  <body data-loadmessage="{{ messages|url_encode }}">
{% endif %}

{% endif %}
