{% if not apps.isAjax %}

{% set blocks = {} %}
{% if block('head') is defined %}
  {% set blocks = {'head': block('head')}|merge(blocks) %}
{% endif %}
{% if block('links') is defined %}
  {% set blocks = {'links': block('links')}|merge(blocks) %}
{% endif %}
{% include 'header.tpl' with blocks %}

{% set template_dir = apps.currentApp('basename') %}

{% if messages is not empty %}
  <body data-loadmessage="{{ messages|url_encode }}">
{% endif %}
{% if withoutGlobalNav != true %}
  <header id="global-nav">
    <nav>
      <ul class="navroot">
        {% if nav|length > 1 %}
          {% for item in nav %}
            {% if loop.first %}
              <li id="nav-application"><a href="?mode=user.response">■</a>
                <ul>
            {% endif %}
              <li><a href="?mode=system.response:change&amp;app={{ item.code }}">{{ item.label ? item.label : item.name }}</a></li>
            {% if loop.last %}
                </ul>
              </li>
            {% endif %}
          {% endfor %}
        {% endif %}
        {% set template = template_dir ~ "/application_nav.tpl" %}
        {% if apps.view.exists(template) %}
          {% include template %}
        {% endif %}
        {% for template in plugin_templates %}
          {% set plugin_template = template ~ '/' ~ template_dir ~ '/plugin_nav.tpl' %}
          {% if apps.view.exists(plugin_template) %}
            {% include plugin_template %}
          {% endif %}
        {% endfor %}
        {% if apps.hasPermission('system') %}
          <li id="nav-system"><a href="#nav-system-sub">システム管理</a>
            <ul id="nav-system-sub">
              {% set template = template_dir ~ "/system_nav.tpl" %}
              {% if apps.view.exists(template) %}
                {% include template %}
              {% endif %}
              {% if apps.hasPermission('user.read') %}
                <li id="nav-user"><a href="?mode=user.response">ユーザー管理</a></li>
              {% endif %}
              {% if apps.hasPermission('root') %}
                <li><a href="?mode=system.response">拡張機能管理</a></li>
                <li><a href="?mode=system.response:plugins">プラグイン管理</a></li>
                <li><a href="?mode=system.response:dbmanage">データベース管理</a></li>
                <li><a href="?mode=system.response:log">システムログ</a></li>
              {% endif %}
            </ul>
          </li>
        {% endif %}
        <li class="spacer"></li>
        <li id="nav-00">
          <span class="logo">Tak-Me <span>System</span></span>
          <a href="?mode=user.response.response:profile">ID: {{ apps.userinfo.uname }}</a>
          <ul>
            {% set template = template_dir ~ "/private_nav.tpl" %}
            {% if apps.view.exists(template) %}
              {% include template %}
            {% endif %}
            <li><a href="?mode=user.response:profile">プロフィール編集</a></li>
            {% if session.origin is defined %}
              <li><a href="?mode=user.response:rewind">アカウント復帰</a></li>
            {% endif %}
            <li><hr><a href="?logout" class="signout" id="signout">サインアウト</a></li>
          </ul>
        </li>
      </ul>
    </nav>
  </header>
{% endif %}

  <div id="container">

{% endif %}

<form id="TMS-mainform" action="{{ form.action }}" method="{{ form.method }}" enctype="{{ form.enctype }}"{% if form.confirm %} data-confirm="{{ form.confirm|url_encode }}"{% endif %}{% if form.target is defined %} target="{{ form.target }}"{% endif %}{% if form.class is defined %} class="{{ form.class is iterable ? form.class|join(' ') : form.class }}"{% endif %}>
      <input type="hidden" name="stub" value="{{ stub }}">
      {% block main %}{% endblock %}
      {% include 'nochange.tpl' %}
    </form>

{% if not apps.isAjax %}
    {% block subform %}{% endblock %}
  </div>
  {% block pagefooter %}{% endblock %}
  {% set template = "alert/pw_security.tpl" %}
  {% if apps.view.exists(template) %}
    {% include template %}
  {% endif %}
{% endif %}
