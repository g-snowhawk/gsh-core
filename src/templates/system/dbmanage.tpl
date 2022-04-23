{% extends "master.tpl" %}

{% block head %}
  <script src="{{ config.global.assets_path }}script/dbmanager.js"></script>
{% endblock %}

{% block main %}
  <div class="wrapper">
    <h1>データベース管理</h1>
    <section>
      <h2>SQL発行</h2>
      <div class="fieldset input-only">
        <textarea name="sql" id="sql" placeholder="ここに直接入力">{{ post.sql }}</textarea>
      </div>
      <div class="fieldset">
        <label for="sqlfile">ファイルから発行</label>
        <div class="input"><input type="file" name="sqlfile" id="sqlfile"></div>
      </div>
      <div class="form-footer">
        <button type="submit" name="mode" value="system.receive:exec-sql" data-confirm="この操作は取り消すことができません。%0A実行してよろしいですか？">実行</button>
      </div>
    </section>
    <hr>
    <section>
      <h2>エクスポート</h2>
      <div class="fieldset">
        <div class="legend">テーブル選択</div>
        <div class="input">
          <div class="choices-group">
          {% for table in tables %}
            <label><input type="checkbox" name="tables[]" value="{{ table }}"{%if table in post.tables %} checked{% endif %}>{{ table }}</label>
          {% endfor %}
          </div>
          <label><input type="checkbox" name="select_all" value="Select all"{%if post.select_all == 'Select all' %} checked{% endif %}>Select all</label>
        </div>
      </div>
      <div class="fieldset">
        <div class="legend">出力オプション</div>
        <div class="input">
          <div>
            <label><input type="radio" name="no_data" value=""{%if post.no_data == '' %} checked{% endif %} class="export-options">テーブル構造&thinsp;&amp;&thinsp;データ</label>
            <label><input type="radio" name="no_data" value="no-data"{%if post.no_data == 'no-data' %} checked{% endif %} class="export-options">テーブル構造のみ</label>
            <label><input type="radio" name="no_data" value="no-create-info"{%if post.no_data == 'no-create-info' %} checked{% endif %} class="export-options">データのみ</label>
          </div>
        </div>
      </div>
      <div class="form-footer">
        <button type="submit" name="mode" value="system.receive:dump-db" class="export-options" data-confirm="この処理には時間がかかることがあります。%0A実行してよろしいですか？">実行</button>
      </div>
    </section>
    {% for table in apps.cnf('database:normalizes') %}
      {% if loop.first %}
        <hr>
        <section>
          <h2>テーブル最適化</h2>
          <div class="fieldset">
            <div class="legend">テーブル選択</div>
            <div class="input">
              <div class="choices-group">
      {% endif %}
      <label><input type="checkbox" name="normalizes[]" value="{{ table }}"{%if table in post.normalizes %} checked{% endif %}>{{ table }}</label>
      {% if loop.last %}
              </div>
            </div>
          </div>
          <div class="form-footer">
            <button type="submit" name="mode" value="system.receive:normalize-table" class="normalize-options" data-confirm="この処理には時間がかかることがあります。%0A実行してよろしいですか？">実行</button>
          </div>
        </section>
      {% endif %}
    {% endfor %}
  </div>
{% endblock %}
