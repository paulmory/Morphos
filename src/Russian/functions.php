<?php
namespace morphos\Russian;

use morphos\Gender;
use morphos\S;

/**
 * Inflects the name in one case.
 * @param string        $fullName Name in "F", "L F" or "L M F" format, where L - last name, M - middle name, F - first name
 * @param string        $case     Case to inflect to.
 *                                Should be one of [[morphos\Cases]] or [[morphos\Russian\Cases]] constants.
 * @param null|string   $gender   Gender of name owner. If null, auto detection will be used.
 *                                Should be one of [[morphos\Gender]] constants.
 * @return string                 Returns string containing the inflection of name to a case.
 */
function inflectName($fullName, $case = null, $gender = null)
{
    if ($case === null) {
        return getNameCases($fullName);
    }

    if (in_array($case, [Gender::MALE, Gender::FEMALE], true)) {
        return getNameCases($fullName, $case);
    }

    $fullName = normalizeFullName($fullName);
    if ($gender === null) $gender = detectGender($fullName);

    $name = explode(' ', $fullName);
    $case = CasesHelper::canonizeCase($case);

    switch (count($name)) {
        case 1:
            $name[0] = FirstNamesInflection::getCase($name[0], $case, $gender);
            break;

        case 2:
            $name[0] = LastNamesInflection::getCase($name[0], $case, $gender);
            $name[1] = FirstNamesInflection::getCase($name[1], $case, $gender);
            break;

        case 3:
            $name[0] = LastNamesInflection::getCase($name[0], $case, $gender);
            $name[1] = FirstNamesInflection::getCase($name[1], $case, $gender);
            $name[2] = MiddleNamesInflection::getCase($name[2], $case, $gender);
            break;

        default:
            return false;
    }

    return implode(' ', $name);
}

/**
 * Inflects the name to all cases.
 * @param string      $fullName Name in "F", "L F" or "L M F" format, where L - last name, M - middle name, F - first name
 * @param null|string $gender   Gender of name owner. If null, auto detection will be used.
 *                              Should be one of [[morphos\Gender]] constants.
 * @return array                Returns an array with name inflected to all cases.
 */
function getNameCases($fullName, $gender = null)
{
    $fullName = normalizeFullName($fullName);
    if ($gender === null) $gender = detectGender($fullName);

    $name = explode(' ', $fullName);

    switch (count($name)) {
        case 1:
            $name[0] = FirstNamesInflection::getCases($name[0], $gender);
            break;

        case 2:
            $name[0] = LastNamesInflection::getCases($name[0], $gender);
            $name[1] = FirstNamesInflection::getCases($name[1], $gender);
            break;

        case 3:
            $name[0] = LastNamesInflection::getCases($name[0], $gender);
            $name[1] = FirstNamesInflection::getCases($name[1], $gender);
            $name[2] = MiddleNamesInflection::getCases($name[2], $gender);
            break;

        default:
            return false;
    }

    return CasesHelper::composeCasesFromWords($name);
}

/**
 * Guesses the gender of name owner.
 * @param string $fullName
 * @return null|string     Null if not detected. One of [[morphos\Gender]] constants.
 */
function detectGender($fullName)
{
    $gender = null;
    $name = explode(' ', S::lower($fullName));
    $nameCount = count($name);
    if (!in_array($nameCount, [2, 3], true)) {
        return false;
    }
    if ($nameCount === 3) {
        $gender = detectGender(implode(' ', [$name[1], $name[2]]));
    }

    if (!$gender) {
        $gender = (isset($name[2]) ? MiddleNamesInflection::detectGender($name[2]) : null) ?:
            LastNamesInflection::detectGender($name[0]) ?:
                FirstNamesInflection::detectGender($name[1]);
    }

    return $gender;
}

/**
 * Normalizes a full name. Swaps name parts to make "L F" or "L M F" scheme.
 * @param string $name Input name
 * @return string      Normalized name
 */
function normalizeFullName($name)
{
    $name = preg_replace('~[ ]{2,}~', null, trim($name));
    return $name;
}

/**
 * Генерация строки с числом и существительным, в правильной форме для сочетания с числом (кол-вом предметов).
 * @param int $count Количество предметов
 * @param string $word Название предмета
 * @param bool $animateness Признак одушевленности
 * @return string Строка в формате "ЧИСЛО [СУЩ в правильно форме]"
 */
function pluralize($count, $word, $animateness = false)
{
    // меняем местами аргументы, если они переданы в старом формате
    if (is_string($count) && is_numeric($word)) {
        list($count, $word) = [$word, $count];
    }
    return $count.' '.NounPluralization::pluralize($count, $word, $animateness);
}
