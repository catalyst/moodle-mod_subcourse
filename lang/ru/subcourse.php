<?php

// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Defines Russian strings of the subcourse module
 *
 * @package     mod_subcourse
 * @category    string
 * @copyright   2013 Yevhen Matasar (Евгений Матасар), matasar.ei@gmail.com
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['currentgrade'] = 'Поточная оценка: {$a}';
$string['errfetch'] = 'Невозможно выполнить выборку оценок: код ошибки {$a}';
$string['errlocalremotescale'] = 'Невозможно выполнить выборку оценок: итоговая оценка использует локальное шкалирование.';
$string['fetchnow'] = 'Выполнить выборку сейчас';
$string['gotocoursename'] = 'Перейти к курсу <a href="{$a->href}">{$a->name}</a>';
$string['hiddencourse'] = '*скрытый*';
$string['lastfetchnever'] = 'Выборка оценок еще не выполнялась';
$string['lastfetchtime'] = 'Последний запрос: {$a}';
$string['modulename'] = 'Подкурс';
$string['modulename_help'] = 'Модуль реализовывает очень простой и полезный функционал. С помощью этого модуля вы можете добавлять подкурсы в курс, как обычные виды деятельности. Оценки всех подкурсов комбинируются в базовом курсе (метакурсе). Это позволяет разбивать курс на отдельные блоки или создавать более сложную структуру курсов.';
$string['modulenameplural'] = 'Подкурсы';
$string['nocoursesavailable'] = 'Нет курсов для выборки оценок';
$string['nosubcourses'] = 'В этом курсе нет подкурсов';
$string['pluginadministration'] = 'Управление подкурсом';
$string['pluginname'] = 'Подкурс';
$string['refcourse'] = 'Ссылка на курс';
$string['refcourse_help'] = 'Студенты должны быть зачислены на подкурс. Выборка оценок будет выполняться с учетом ссылки на курс.

Вы должны быть преподавателем, что бы увидеть этот список. Вы можете попросить администратора настроить этот подкурс, что бы вы могли выполнять выборку оценок с других курсов.';
$string['refcoursecurrent'] = 'Оставить текущую ссылку';
$string['refcourselabel'] = 'Выполнить выборку оценок из';
$string['refcoursenull'] = 'Ссылка на курс не настроена';
$string['subcoursename'] = "Имя подкурса";

//Capabilities
$string['subcourse:addinstance'] = "Добавлять объект";
$string['subcourse:begraded'] = "Быть оцененным";
$string['subcourse:fetchgrades'] = "Делать выборку оценок";