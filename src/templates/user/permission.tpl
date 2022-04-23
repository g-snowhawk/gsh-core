{% set priv = perms.global %}
<section class="permission" id="global-permission">
  <h2><a href="#permission-editor-global" class="accordion-switcher">グローバル権限設定</a></h2>
  <div id="permission-editor-global" class="accordion">
    <table>
      <thead>
        <tr>
          <td>権限適用範囲</td>
          <td>実行</td>
          <td>作成</td>
          <td>読取</td>
          <td>更新</td>
          <td>削除</td>
          <td>その他</td>
        </tr>
      </thead>
      <tbody>
        {% for item in nav %}
          <tr>
            <th>{{ item.name }}</th>
            <td><input type="checkbox" name="perm[{{ item.code }}.exec]" value="1"{% if post.perm[item.code ~ '.exec'] != 0 %} checked{% endif %}></td>
            <td>-</td>
            <td>-</td>
            <td>-</td>
            <td>-</td>
            <td>-</td>
          </tr>
        {% endfor %}
        <tr>
          <th>ユーザー管理</th>
          <td>-</td>
          {% if apps.userinfo.admin == 1 or priv.user.grant == 1 %}
            <td>{% if apps.userinfo.admin == 1 or priv.user.create == 1 %}<input type="checkbox" name="perm[user.create]" value="1"{% if post.perm['user.create'] == 1 %} checked{% endif %}>{% else %}-{% endif %}</td>
            <td>{% if apps.userinfo.admin == 1 or priv.user.read   == 1 %}<input type="checkbox" name="perm[user.read]"   value="1"{% if post.perm['user.read']   == 1 %} checked{% endif %}>{% else %}-{% endif %}</td>
            <td>{% if apps.userinfo.admin == 1 or priv.user.update == 1 %}<input type="checkbox" name="perm[user.update]" value="1"{% if post.perm['user.update'] == 1 %} checked{% endif %}>{% else %}-{% endif %}</td>
            <td>{% if apps.userinfo.admin == 1 or priv.user.delete == 1 %}<input type="checkbox" name="perm[user.delete]" value="1"{% if post.perm['user.delete'] == 1 %} checked{% endif %}>{% else %}-{% endif %}</td>
          {% else %}
            <td>-</td>
            <td>-</td>
            <td>-</td>
            <td>-</td>
          {% endif %}
          <td>{% if apps.userinfo.admin == 1 or priv.user.alias  == 1 %}<input type="checkbox" name="perm[user.alias]"  value="1"{% if post.perm['user.alias']  == 1 %} checked{% endif %}><small>エイリアス</small>{% else %}-{% endif %}</td>
        </tr>
        {% set template = apps.currentApp('basename') ~ "/global_permission.tpl" %}
        {% if apps.view.exists(template) %}
          {% include template %}
        {% endif %}
      </tbody>
    </table>
  </div>
</section>
