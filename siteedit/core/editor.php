<?php
chdir('../../');
require $_SERVER['DOCUMENT_ROOT'] . '/siteedit/inc.php';

if (isset($_REQUEST['page'])) {
    $_SESSION['seedit_editpage'] = substr($_REQUEST['page'], 0, 255);
}
$editpage = (!empty($_SESSION['seedit_editpage'])) ? $_SESSION['seedit_editpage'] : 'home';
if (isset($_REQUEST['setoken'])) {
    $_SESSION['seedit_secookie'] = $_REQUEST['setoken'];
    $_SESSION['seedit_api'] = getUrlHost();
    $_SESSION['seedit_target'] = $_SERVER['HTTP_REFERER'];
    //setcookie('seedit_secookie', $_SESSION['seedit_secookie']);

    header('Location: ?page='.$editpage);
}

//if (!empty($_COOKIE['seedit_secookie']))
//    $_SESSION['seedit_secookie'] = $_COOKIE['seedit_secookie'];

if (empty($_SESSION['seedit_secookie'])) exit;


if (isset($_POST['mode'])) {
    $_SESSION['seedit_mode'] = $_POST['mode'];
}



$method = (isset($_SESSION['seedit_mode']) && $_SESSION['seedit_mode'] == 1) ? 'design' : 'text';

if (isset($_POST['save']) && $_COOKIE['seedit_token'] == $_POST['token']) {
    if ($method == 'text') {
        request('Editor', 'SAVE', array('name'=> $editpage, 'content'=>$_POST['save']));
        echo 'ok';
    }
    exit;
}


$base = getUrlHost() . '/www/';
$page = (isset($_GET['page'])) ? $_GET['page'] : '';

function request($object='Editor', $method = 'INFO', $data = array())
{
    //$data_url = http_build_query ($data);
    $data_url = json_encode($data); //
    $data_len = strlen ($data_url);
    $opts = array(
        "http" => array(
            "method" => $method,
            "header" => "Accept-language: en\r\n" .
                "Content-Length: $data_len\r\n" .
                "Content-Type:application/x-www-form-urlencoded; charset=UTF-8\r\n".
                "Secookie: {$_SESSION['seedit_secookie']}\r\n",
            "content" => $data_url
        )
    );
    $context = stream_context_create($opts);
    return json_decode(file_get_contents($_SESSION['seedit_api'].'/api/CMS/'.$object.'/', false, $context), true);
}





if (isset($_SESSION['seedit_mode']) && $_SESSION['seedit_mode'] == 1) {
    $result = request('Design', 'INFO', array('name'=> $editpage));
    $content = '';
    if ($result['status'] == 'success') {
        $content = str_replace(array('<script', '</script', '{{ base }}', '<?', '?>'),
            array('<_cript', '</_cript', $base, '<!--~~?', '?~~-->'), file_get_contents($result['url']));
        $pages = $result['pages'];
    }
} else {
    $result = request('Editor', 'INFO', array('name'=> $editpage));
    $content = '';
    if ($result['status'] == 'success') {
        $content = $result['content'];
        $pages = $result['pages'];
    }
}
foreach ($pages as $page) {
    if ($page['name'] == $editpage) {
        $titlepage = $page['title'];
        break;
    }
}



?>
<!doctype html>
<html id="seedit-visual" class="<?php echo $method ?>">
<head><title>Визуальный редактор - seedit</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <link rel="stylesheet" href="/admin/seedit.css?v=1501402694">
<body>

<iframe>Не работает iframe</iframe>
<script type="text/template" id="seedit-base">
<base href="<?php echo $base ?>">

</script>
<div id="seedit-panel">
    <div>
        <div id="console"></div>
        <ol id="mode-buttons">
            <li class="seedit-active"><a href="<?php echo $_SESSION['seedit_target'] ?>">Назад</a>
            </li>
            <li id="seedit-design-editor"<?php if ($method != 'design') echo 'class="seedit-active"' ?>
                title="Редактор дизайна">Дизайн
            </li>
            <li id="seedit-text-editor"<?php if ($method == 'design') echo 'class="seedit-active"' ?>
                title="Визуальный редактор">Текст
            </li>
        </ol>
        <ul>
            <li>
                <a title="Cтраница"><?php echo (utf8_strlen($titlepage) < 12) ? $titlepage : utf8_substr($titlepage,0, 11).'..' ?></a>
                <div id="seedit-files">
                    <ul>
                        <?php foreach ($pages as $page):?>
                            <li><a href="?page=<?php echo $page['name'] ?>"><?php echo $page['title'] ?></a></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </li>
        </ul>

        <!--ul>
            <li><a title="Файлы">Файлы</a>
                <div id="seedit-files">
                    <ol>
                        <li>Имя</li>
                        <li>Размер</li>
                        <li>Изменён</li>
                        <li>Меню</li>
                    </ol>
                    <ul><li>
                            <ol class="seedit-folder">
                                <li><a data-url="{url}">{name}</a></li>
                                <li>{size}</li>
                                <li>{date}</li>
                                <li><i title="Добавить файл"></i></li>
                            </ol>
                            <ul></ul>
                        </li></ul>
                </div>
            </li>
            <li><a title="Настройки">Настройки</a>
                <div id="seedit-settings">
                    <fieldset>
                        <legend>Авторизация</legend>
                        <dl>
                            <dt title="Новый пароль">Новый пароль:</dt>
                            <dd><input type="password" maxlength="14"><a></a></dd>
                            <dt title="Ограничивает максимально допустимое количество ошибок при введении пароля. Если количество ошибок с одного IP-адреса превышает это значение, пользователь блокируется на период, который можно настроить в следующем параметре.">Попыток авторизации с неверным паролем:</dt>
                            <dd defaultValue="5"><input type="text" maxlength="2" value="5"></dd>
                            <dt title="Отвечает за длительность блокировки пользователей, превысивших максимально допустимое количество ошибок при введении пароля. После истечения указанного периода пользователь получает одну дополнительную попытку авторизации.">Длительность блокировки в часах:</dt>
                            <dd defaultValue="1"><input type="text" maxlength="7" value="1"></dd>
                            <dt title="Ограничивает время жизни сессии после потери системой возможности поддерживать соединение с сервером.">Завершение сессии после бездействия в минутах:</dt>
                            <dd defaultValue="30"><input type="text" maxlength="3" value="30"></dd>
                            <dd title="В случае включения выход из системы будет сопровождаться перенаправлением пользователя на сайт по адресу последней редактируемой страницы или файла." defaultValue="0"><label><input type="checkbox">
                                    <em></em>Перенаправлять на сайт после выхода из системы</label></dd>
                        </dl>
                    </fieldset>
                    <fieldset>
                        <legend>Визуальный редактор</legend>
                        <dl>
                            <dd title="Скрипты сайта могут мешать редактированию некоторых элементов в визуальном редакторе. Отключение скриптов может сделать такие элементы доступными для визуального редактирования." defaultValue="1"><label><input type="checkbox" checked>
                                    <em></em>Включить скрипты сайта во время редактирования</label></dd>
                            <dd title="Стили сайта могут мешать редактированию некоторых элементов в визуальном редакторе. Отключение стилей может помочь добраться до таких элементов в визуальном редакторе." defaultValue="1"><label><input type="checkbox" checked>
                                    <em></em>Включить стили сайта во время редактирования</label></dd>
                            <dd title="Опция определяет поведение всех функций замены картинок перетаскиванием на картинках-ссылках. В случае включения система будет вместе с картинкой менять адрес ссылки на адрес вставленного файла-картинки, но только в тех случаях, когда существующая ссылка так же имеет адрес файла-картинки." defaultValue="1"><label><input type="checkbox">
                                    <em></em>Автоматически менять адреса ссылок у картинок-ссылок</label></dd>
                            <dd title="Опция контролирует функцию замены картинок перетаскиванием файла в окно браузера. В случае включения система будет автоматически перезаписывать файл, имя которого совпадает с именем вставляемой картинки. В случае отключения новый файл переименовывается." defaultValue="0"><label><input type="checkbox">
                                    <em></em>Перезаписывать старый файл, если имена файлов совпадают</label></dd>
                        </dl>
                    </fieldset>
                    <fieldset>
                        <legend>Редактор исходного кода</legend>
                        <dl>
                            <dt title="Когда пользователь вводит новый код в редакторе исходного кода, система ждет паузу в процессе ввода, чтобы проверить и оформить новый код. Данный параметр определяет, какой должна быть пауза, чтобы система могла начать переоформление. Чем меньше его значение, тем чаще будет переоформляться новый код в процессе ввода. Увеличение значения может заметно снизить нагрузку на браузер.">Обновлять код после бездействия в миллисекундах:</dt>
                            <dd defaultValue="200"><input type="text" maxlength="7" value="200"></dd>
                            <dt title="Определяет максимальную глубину отката изменений в редакторе исходного кода через Ctrl+Z.">Количество шагов для отката изменений через Ctrl+Z:</dt>
                            <dd defaultValue="50"><input type="text" maxlength="3" value="50"></dd>
                            <dd title="Подсветка синтаксиса и нумерация строк облегчает редактирование кода. Отключение может заметно снизить нагрузку на браузер." defaultValue="1"><label><input type="checkbox" checked>
                                    <em></em>Включить подсветку синтаксиса и нумерацию строк</label></dd>
                        </dl>
                    </fieldset>
                    <fieldset>
                        <legend>Система</legend>
                        <dl>
                            <dt title="Значение должно содержать имя реально существующего в корне сайта файла. Система будет открывать в редакторе этот файл в качестве главной страницы сайта, а также во всех случаях, когда файл для редактирования не выбран или не может быть открыт">Главная страница или файл сайта:</dt>
                            <dd defaultValue="index.html"><input type="text" maxlength="30" value="index.html"></dd>
                            <dt title="Параметр ограничивает максимальное количество точек восстановления, которые система может хранить на сервере. Если количество точек превысит его значение, каждая новая точка будет перезаписывать одну самую старую из всех существующих. Нулевое значение полностью отключает систему резервного копирования и восстановления.">Максимальное количество точек восстановления:</dt>
                            <dd defaultValue="5"><input type="text" maxlength="2" value="20"></dd>
                            <dd title="Сообщать о появлении новых версий системы" defaultValue="1"><label><input type="checkbox" checked>
                                    <em></em>Сообщать о появлении новых версий системы</label></dd>
                            <dd title="Сообщать о появлении новых beta-версий системы" defaultValue="0"><label><input type="checkbox" checked>
                                    <em></em>Сообщать о появлении новых beta-версий системы</label></dd>
                            <dt title="Язык интерфейса">Язык интерфейса:</dt>
                            <dd defaultValue="index.html">
                                <ul><li title="Английский"><input type="radio" name="lang" value="en">en</li><li title="Русский"><input type="radio" name="lang" value="ru" checked>ru</li></ul>
                            </dd>
                        </dl>
                    </fieldset>
                    <p><input type="button" value="Сохранить" disabled><a title="Восстановить настройки по умолчанию"></a></p></div>
            </li>
        </ul-->
        <div id="seedit-tags">
            <ul data-item="<li>{tagname}<i><i></i></i></li>"></ul>
            <p>
                <i id="seedit-tag-clone" title="Клонировать"></i>
                <i id="seedit-tag-top" title="Переместить вверх"></i>
                <i id="seedit-tag-bottom" title="Переместить вниз"></i>
                <i id="seedit-tag-delete" title="Удалить"></i>
            </p>
        </div>
        <ul>
            <li><input id="seedit-button-save" type="button" value="Сохранить" title="Сохранить" disabled></li>
            <!--li><input id="seedit-button-logout" type="button" value="Выход" title="Выход" disabled data-confirm="Не сохранять"></li-->
        </ul>
        <p><samp id="seedit-messages" data-saving="Сохранение" data-saved="Изменения сохранены"
                 data-not-saved="Изменения не сохранены"
                 data-reset-session="Сброс сессии" data-access-closed="Доступ закрыт"
                 data-request-rejected="Запрос отклонён" data-request-blocked="Настройки сервера блокируют запрос"
                 data-no-response="Сервер не отвечает" data-not-writable="Нет прав на запись в файл"
                 data-old-browser="Нужен новый браузер"
                 data-new-version="Доступна новая версия" data-need-update="Необходимо установить новую версию"
                 data-install="установить"
                 data-not-install="Не устанавливать" data-download-installer="Загрузка установочных файлов"
                 data-system-update="Обновление системных файлов"
                 data-update-error="Не удалось перезаписать системные файлы"
                 data-install-complete="Установка завершена" data-activation-complete="Расширенная версия активирована"
                 data-no-connect="Нет соединения с сервером" data-password-hashing="Создание хеша"
                 data-pass-complexity="14991" data-uploading="Загрузка файлов"
                 data-uploading-complete="Загрузка файлов завершена" data-uploading-error="Сбой в загрузке файлов"
                 data-extension-error="Файл имеет недопустимое расширение"
                 data-count-limit="Лимит сервера на количество файлов за раз"
                 data-size-limit="Лимит сервера на размер файла" data-file-deletion="Удаление файла"
                 data-file-deleted="Файл успешно удалён"
                 data-deletion-error="Сбой в удалении файла" data-file-recovery="Восстановление файлов"
                 data-recovery-success="Восстановление успешно завершено"
                 data-recovery-error="Восстановление завершилось ошибкой"
                 data-backup-error="Проблема с созданием резервной копии" data-file-replacing="Замена файла"
                 data-incorrect-link="Ссылка не соответствует формату"
                 data-unknown-relation="Элемент имеет неизвестные зависимости"
                 data-element-busy="Элемент используется скриптами" data-disable-script="Отключить скрипты сайта"
                 data-disable="отключить"
                 data-disabling-scripts="Отключение скриптов сайта" data-scripts-disabled="Скрипты сайта отключены"
                 data-show-password="Показать пароль" data-hide-password="Скрыть пароль"
                 data-post-max-size="1047527424" data-upload-max-filesize="1047527424"
                 data-max-file-uploads="20" data-session-autoreset="30"
                 data-link-replacing="0" data-site-scripts="1"
                 data-site-styles="1" data-logout-to-site="0" data-ip="94.50.4.166" data-sip="5.101.153.108"
                 data-system-url="/admin/" data-is-edited="" data-version="1501432394"
                 data-update-final="1" data-update-beta="1"></samp>
        <noscript><samp>Нужен JavaScript</samp></noscript>
        <i></i></p></div>

</div>
<script src="/admin/seedit.js?v=1501402694"></script>
<script type="text/template" id="seedit-block">
    <?php if ($method != 'design'): ?>
        <edit class="seedit-block" contenteditable></edit>
    <?php endif ?>
</script>

<script type="text/template" id="seedit-style">
    @keyframes drop{0%{opacity:0.6;}49%{opacity:0.6;}50%{opacity:1;}99%{opacity:1;}}
    .seedit-block{outline:none;font-style:inherit;cursor:text}
    .seedit-focus{outline:4px solid #f2ca00 !important;outline-offset:8px}
    .seedit-disabled{outline-color:#f00 !important}
    .seedit-dragover{outline:4px solid #adc8fe;outline-offset:-4px}
    .seedit-drop{animation:drop 70ms infinite linear}
    .seedit-iframe{display:block; position:absolute}
    .seedit-design-block {outline:4px solid #caf200 !important;outline-offset:8px; }
</script>

<script type="text/template" id="seedit-source">
    <?php echo $content; ?>
</script>

</body>
</html>