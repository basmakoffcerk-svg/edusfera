<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Validation Language Lines
    |--------------------------------------------------------------------------
    */

    'accepted' => 'Вы должны принять :attribute.',
    'accepted_if' => 'Вы должны принять :attribute, если :other равно :value.',
    'active_url' => 'Поле :attribute не является действительным URL.',
    'after' => 'Поле :attribute должно быть датой после :date.',
    'alpha' => 'Поле :attribute может содержать только буквы.',
    'alpha_dash' => 'Поле :attribute может содержать только буквы, цифры, дефис и подчеркивание.',
    'alpha_num' => 'Поле :attribute может содержать только буквы и цифры.',
    'array' => 'Поле :attribute должно быть массивом.',
    'before' => 'Поле :attribute должно быть датой до :date.',
    'between' => [
        'array' => 'Количество элементов в :attribute должно быть от :min до :max.',
        'file' => 'Размер файла :attribute должен быть от :min до :max КБ.',
        'numeric' => 'Поле :attribute должно быть между :min и :max.',
        'string' => 'Количество символов в :attribute должно быть от :min до :max.',
    ],
    'boolean' => 'Поле :attribute должно быть логическим значением.',
    'confirmed' => 'Подтверждение поля :attribute не совпадает.',
    'current_password' => 'Неверный текущий пароль.',
    'date' => 'Поле :attribute не является датой.',
    'date_equals' => 'Поле :attribute должно быть датой, равной :date.',
    'date_format' => 'Поле :attribute не соответствует формату :format.',
    'declined' => 'Поле :attribute должно быть отклонено.',
    'different' => 'Поля :attribute и :other должны различаться.',
    'digits' => 'Длина цифрового поля :attribute должна быть :digits.',
    'digits_between' => 'Длина цифрового поля :attribute должна быть от :min до :max.',
    'email' => 'Поле :attribute должно быть действительным электронным адресом.',
    'ends_with' => 'Поле :attribute должно заканчиваться одним из следующих значений: :values.',
    'enum' => 'Выбранное значение для :attribute некорректно.',
    'exists' => 'Выбранное значение для :attribute недействительно.',
    'file' => 'Поле :attribute должно быть файлом.',
    'filled' => 'Поле :attribute обязательно для заполнения.',
    'gt' => [
        'array' => 'Количество элементов в :attribute должно быть больше :value.',
        'file' => 'Размер файла :attribute должен быть больше :value КБ.',
        'numeric' => 'Поле :attribute должно быть больше :value.',
        'string' => 'Количество символов в :attribute должно быть больше :value.',
    ],
    'gte' => [
        'array' => 'Количество элементов в :attribute должно быть не меньше :value.',
        'file' => 'Размер файла :attribute должен быть не меньше :value КБ.',
        'numeric' => 'Поле :attribute должно быть не меньше :value.',
        'string' => 'Количество символов в :attribute должно быть не меньше :value.',
    ],
    'image' => 'Поле :attribute должно быть изображением.',
    'in' => 'Выбранное значение для :attribute ошибочно.',
    'in_array' => 'Поле :attribute не существует в :other.',
    'integer' => 'Поле :attribute должно быть целым числом.',
    'ip' => 'Поле :attribute должно быть действительным IP-адресом.',
    'json' => 'Поле :attribute должно быть валидной JSON-строкой.',
    'lt' => [
        'array' => 'Количество элементов в :attribute должно быть меньше :value.',
        'file' => 'Размер файла :attribute должен быть меньше :value КБ.',
        'numeric' => 'Поле :attribute должно быть меньше :value.',
        'string' => 'Количество символов в :attribute должно быть меньше :value.',
    ],
    'lte' => [
        'array' => 'Количество элементов в :attribute должно быть не больше :value.',
        'file' => 'Размер файла :attribute должен быть не больше :value КБ.',
        'numeric' => 'Поле :attribute должно быть не больше :value.',
        'string' => 'Количество символов в :attribute должно быть не больше :value.',
    ],
    'max' => [
        'array' => 'Количество элементов в :attribute не может превышать :max.',
        'file' => 'Размер файла :attribute не может превышать :max КБ.',
        'numeric' => 'Поле :attribute не может быть больше :max.',
        'string' => 'Количество символов в :attribute не может превышать :max.',
    ],
    'mimes' => 'Поле :attribute должно быть файлом одного из следующих типов: :values.',
    'mimetypes' => 'Поле :attribute должно быть файлом одного из следующих типов: :values.',
    'min' => [
        'array' => 'Количество элементов в :attribute должно быть не менее :min.',
        'file' => 'Размер файла :attribute должен быть не менее :min КБ.',
        'numeric' => 'Поле :attribute должно быть не менее :min.',
        'string' => 'Количество символов в :attribute должно быть не менее :min.',
    ],
    'numeric' => 'Поле :attribute должно быть числом.',
    'password' => [
        'letters' => 'Поле :attribute должно содержать хотя бы одну букву.',
        'mixed' => 'Поле :attribute должно содержать хотя бы одну заглавную и одну строчную букву.',
        'numbers' => 'Поле :attribute должно содержать хотя бы одну цифру.',
        'symbols' => 'Поле :attribute должно содержать хотя бы один специальный символ.',
        'uncompromised' => 'Указанный :attribute был скомпрометирован в утечках данных. Пожалуйста, выберите другой :attribute.',
    ],
    'present' => 'Поле :attribute должно присутствовать.',
    'regex' => 'Формат поля :attribute недействителен.',
    'required' => 'Поле :attribute обязательно для заполнения.',
    'required_array_keys' => 'Поле :attribute должно содержать ключи: :values.',
    'required_if' => 'Поле :attribute обязательно для заполнения, когда :other равно :value.',
    'required_unless' => 'Поле :attribute обязательно для заполнения, когда :other не равно :values.',
    'required_with' => 'Поле :attribute обязательно для заполнения, когда :values указано.',
    'required_with_all' => 'Поле :attribute обязательно для заполнения, когда указаны :values.',
    'required_without' => 'Поле :attribute обязательно для заполнения, когда :values не указано.',
    'required_without_all' => 'Поле :attribute обязательно для заполнения, когда ни одно из :values не указано.',
    'same' => 'Поля :attribute и :other должны совпадать.',
    'size' => [
        'array' => 'Количество элементов в :attribute должно быть равным :size.',
        'file' => 'Размер файла :attribute должен быть равен :size КБ.',
        'numeric' => 'Поле :attribute должно быть равным :size.',
        'string' => 'Количество символов в :attribute должно быть равным :size.',
    ],
    'string' => 'Поле :attribute должно быть строкой.',
    'timezone' => 'Поле :attribute должно быть действительным часовым поясом.',
    'unique' => 'Такое значение для :attribute уже зарегистрировано.',
    'uploaded' => 'Загрузка файла :attribute не удалась.',
    'url' => 'Формат поля :attribute недействителен.',
    'uuid' => 'Поле :attribute должно быть действительным UUID.',

    /*
    |--------------------------------------------------------------------------
    | Custom Validation Attributes
    |--------------------------------------------------------------------------
    */

    'attributes' => [
        'email' => 'электронной почты (e-mail)',
        'phone' => 'телефона',
        'password' => 'пароль',
        'firstName' => 'имя',
        'lastName' => 'фамилия',
        'name' => 'имя',
        'role' => 'роль',
        'code' => 'промокод',
    ],

];
