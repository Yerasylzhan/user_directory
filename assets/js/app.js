
webix.ui({
    container: "app",
    rows: [
        { view: "toolbar", elements: [
            { view: "label", label: "Справочник пользователей" },
            { view: "button", value: "Добавить пользователя", width: 200, align: "right", click: function() {
                showUserForm();
            }}
        ]},
        {
            view: "datatable",
            id: "usersTable",
            autoConfig: true,
            url: "api/users.php",
            select: "row",
            columns: [
                { id: "id", header: "ID", width: 50 },
                { id: "full_name", header: "ФИО", fillspace: true },
                { id: "login", header: "Логин", width: 150 },
                { id: "role", header: "Роль", width: 150 },
                { id: "is_blocked", header: "Заблокирован", width: 100, template: function(obj) {
                    return obj.is_blocked ? "Да" : "Нет";
                }},
                { header: "Действия", template: function(obj) {
                    return `<button class="webix_button webix_danger" onclick="blockUser(${obj.id})">Блокировать</button>
                            <button class="webix_button webix_primary" onclick="editUser(${obj.id})">Редактировать</button>`;
                }, width: 200 }
            ],
            pager: "pagerA"
        },
        { view: "pager", id: "pagerA", size: 10, group: 5 }
    ]
});

function showUserForm(user = null) {
    webix.ui({
        view: "window",
        id: "userWindow",
        head: user ? "Редактировать пользователя" : "Добавить пользователя",
        width: 500,
        position: "center",
        modal: true,
        body: {
            view: "form",
            id: "userForm",
            elements: [
                { view: "text", label: "ФИО", name: "full_name", required: true },
                { view: "text", label: "Логин", name: "login", required: true },
                { view: "text", type: "password", label: "Пароль", name: "password", required: !user },
                { view: "combo", label: "Роль", name: "role_id", options: {
                    url: "api/roles.php",
                    template: "#name#",
                    body: {
                        dataFeed: "api/roles.php"
                    }
                }, required: true },
                { margin: 20, cols: [
                    { view: "button", value: "Сохранить", css: "webix_primary", click: function() {
                        let form = $$("userForm");
                        if (form.validate()) {
                            let values = form.getValues();
                            if (user) {
                                values.id = user.id;
                                webix.ajax().put("api/users.php", values, function(text, xml, xhr) {
                                    let response = JSON.parse(text);
                                    if (response.success) {
                                        $$("usersTable").reload();
                                        $$("userWindow").close();
                                        webix.message("Пользователь обновлён");
                                    } else {
                                        webix.message({ type:"error", text: response.error });
                                    }
                                });
                            } else {
                                webix.ajax().post("api/users.php", values, function(text, xml, xhr) {
                                    let response = JSON.parse(text);
                                    if (response.success) {
                                        $$("usersTable").reload();
                                        $$("userWindow").close();
                                        webix.message("Пользователь добавлен");
                                    } else {
                                        webix.message({ type:"error", text: response.error });
                                    }
                                });
                            }
                        }
                    }},
                    { view: "button", value: "Отмена", click: function() {
                        $$("userWindow").close();
                    }}
                ]}
            ],
            rules: {
                "full_name": webix.rules.isNotEmpty,
                "login": webix.rules.isNotEmpty,
                "password": function(value, obj) {
                    if (obj.id) return true;
                    return webix.rules.isNotEmpty(value);
                },
                "role_id": webix.rules.isNotEmpty
            }
        }
    }).show();

    if (user) {
        $$("userForm").setValues({
            full_name: user.full_name,
            login: user.login,
            role_id: getRoleIdByName(user.role)
        });
    }
}

function getRoleIdByName(name) {
    let roles = [];
    webix.ajax().get("api/roles.php", function(text) {
        roles = JSON.parse(text);
    });
    for (let role of roles) {
        if (role.name === name) return role.id;
    }
    return null;
}

function editUser(id) {
    webix.ajax().get("api/users.php?id=" + id, function(text) {
        let user = JSON.parse(text)[0];
        showUserForm(user);
    });
}

function blockUser(id) {
    webix.confirm({
        title: "Подтверждение",
        text: "Вы уверены, что хотите заблокировать этого пользователя?"
    }).then(function() {
        webix.ajax().del("api/users.php?id=" + id, {}, function(text) {
            let response = JSON.parse(text);
            if (response.success) {
                $$("usersTable").reload();
                webix.message("Пользователь заблокирован");
            } else {
                webix.message({ type:"error", text: response.error });
            }
        });
    });
}
