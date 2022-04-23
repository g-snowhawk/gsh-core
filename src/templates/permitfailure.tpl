{% extends "master.tpl" %}

{% block main %}
  <div class="alert">
    <section>
      <h2 data-file="{{ error.file }}" data-line="{{ error.line }}" data-code="{{ error.code }}">{{ alert }}</h2>
    </section>
  </div>
{% endblock %}
