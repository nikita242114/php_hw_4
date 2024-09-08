Д/З

Лог выполнения
Задание
Придумайте класс, который описывает любую сущность из предметной области библиотеки: книга, шкаф, комната и т.п.
Опишите свойства классов из п.1 (состояние).
Опишите поведение классов из п.1 (методы).
Придумайте наследников классов из п.1. Чем они будут отличаться?
Создайте структуру классов ведения книжной номенклатуры.
Есть абстрактная книга.
Есть цифровая книга, бумажная книга.
У каждой книги есть метод получения на руки. У цифровой книги надо вернуть ссылку на скачивание, а у физической – адрес библиотеки, где ее можно получить. У всех книг формируется в конечном итоге статистика по кол-ву прочтений. Что можно вынести в абстрактный класс, а что надо унаследовать?
Дан код:

class A {
    public function foo() {
        static $x = 0;
        echo ++$x;
    }
}
$a1 = new A(); // Ничего, тк. нет вывода, просто создание
$a2 = new A(); // Ничего, тк. нет вывода, просто создание
$a1->foo(); // 1 = статичное св-во $x = $x(0) + 1 -> 1
$a2->foo(); // 2 = статичное св-во $x = $x(1) + 1 -> 2
$a1->foo(); // 3
$a2->foo(); // 4
Что он выведет на каждом шаге? Почему? Немного изменим п.5

class A {
    public function foo() {
        static $x = 0;
        echo ++$x;
    }
}
class B extends A {
}
$a1 = new A(); // Ничего, тк. нет вывода, просто создание
$b1 = new B(); // Ничего, тк. нет вывода, просто создание
$a1->foo(); // 1 -> A::x = A::x(0) + 1
$b1->foo(); // 1 -> B::x = B::x(0) + 1
$a1->foo(); // 2 -> A::x = A::x(1) + 1
$b1->foo(); // 2 -> B::x = B::x(1) + 1
Что он выведет теперь?