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
 * Defines Ukranian strings of the subcourse module
 *
 * @package     mod_subcourse
 * @category    string
 * @copyright   2013 Yevhen Matasar (Євген Матасар), matasar.ei@gmail.com
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['currentgrade'] = 'Поточна оцінка: {$a}';
$string['errfetch'] = 'Неможливо виконати вибірку оцінок: код помилки {$a}';
$string['errlocalremotescale'] = 'Неможливо виконати вибірку оцінок: підсумкова оцінка використовує локальне шкалювання.';
$string['fetchnow'] = 'Виконати вибірку зараз';
$string['gotocoursename'] = 'Перейди до курсу <a href="{$a->href}">{$a->name}</a>';
$string['hiddencourse'] = '*прихований*';
$string['lastfetchnever'] = 'Вибірка оцінок ще не виконувалась';
$string['lastfetchtime'] = 'Останній запит: {$a}';
$string['modulename'] = 'Підкурс';
$string['modulename_help'] = 'Модуль реалізує дуже просту і корисну функціональність. За допомогою цього модуля ви можете додавати підкурси в курс, як звичайні види діяльності. Оцінки всіх підкурсів комбінуються в базовому курсі (метакурсі). Це дозволяє розбивати курси на окремі блоки, або створювати більш складну структуру курсів.';
$string['modulenameplural'] = 'Підкурси';
$string['nocoursesavailable'] = 'Немає курсів для вибірки оцінок';
$string['nosubcourses'] = 'В цьому курсі немає підкурсів';
$string['pluginadministration'] = 'Кервання підкусом';
$string['pluginname'] = 'Підкурс';
$string['refcourse'] = 'Посилання на курс';
$string['refcourse_help'] = 'Студенти мають бути зараховані на підкурс. Оцінки будуть вибиратися з урахуванням посилання на курс.

Ви повинні бути викладачем, щоб побачити цей список. Ви можете запитати адміністратора налаштувати вам цей підкурс, щоб ви могли робити вибірку оцінок з інших курсів.';
$string['refcoursecurrent'] = 'Залишити поточне посилання';
$string['refcourselabel'] = 'Зробити вибірку оцінок з';
$string['refcoursenull'] = 'Посилання на курс не налаштоване';
$string['subcoursename'] = "Ім'я підкурсу";

//Capabilities
$string['subcourse:addinstance'] = "Додавати об'єкт";
$string['subcourse:begraded'] = "Бути оціненим";
$string['subcourse:fetchgrades'] = "Робити вибірку оцінок";