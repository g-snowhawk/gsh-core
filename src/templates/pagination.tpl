{% set pager_format = pager.format %}
{% set pager_total = pager.total %}
{% set pager_current = pager.current %}
{% set pager_prev = pager.prev %}
{% set pager_next = pager.next %}
{% set pager_start = 1 %}
{% set pager_end = pager_total %}

{% if columnCount is defined and pager_end > columnCount %}
  {% set columnOffset = (columnCount / 2)|round %}
  {% set pager_start = pager_current - columnOffset %}
  {% if pager_start < columnOffset %}
    {% set pager_start = 1 %}
  {% endif %}
  {% set pager_start_max = pager_total - columnCount %}
  {% if pager_start > pager_start_max %}
    {% set pager_start = pager_start_max %}
  {% endif %}
  {% if pager_start >= pager_start_max - 1 and pager_end > pager_total - columnOffset %}
    {% set pager_start = pager_start_max %}
  {% endif %}
  {% set pager_end = pager_start + columnCount %}
{% endif %}

{% if pager_total > 1 %}
  {% for i in pager_start..pager_end %}
    {% if loop.first %}
      <div class="pager">
        {% if pager_prev > 0 %}
          <a href="{{ pager_format|format(pager_prev) }}" class="prev">前へ</a>
        {% else %}
          <span class="prev">前へ</span>
        {% endif %}
        {% if pager_start > 1 %}
          <a href="{{ pager_format|format(1) }}">1</a><span>..</span>
        {% endif %}
      {% endif %}
      {% if pager_current != i %}
        <a href="{{ pager_format|format(i) }}">{{ i }}</a>
      {% else %}
        <b>{{ i }}</b>
      {% endif %}
      {% if loop.last %}
        {% if pager_end < pager_total %}
          <span>..</span><a href="{{ pager_format|format(pager_total) }}">{{ pager_total }}</a>
        {% endif %}
        {% if pager_next <= pager_total %}
          <a href="{{ pager_format|format(pager_next) }}" class="next">次へ</a>
        {% else %}
          <span class="next">次へ</span>
        {% endif %}
      </div>
    {% endif %}
  {% endfor %}
{% endif %}
