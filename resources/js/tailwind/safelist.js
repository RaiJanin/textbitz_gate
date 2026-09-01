const colors = ['red', 'blue', 'green', 'yellow', 'purple', 'orange', 'gray'];

export default colors.flatMap(color => [
    `bg-${color}-100`,
    `bg-${color}-500`,
    `text-${color}-700`,
    `text-${color}-500`,
    `dark:bg-${color}-300`,
    `dark:bg-${color}-200/80`,
    `dark:text-${color}-300`,
    `dark:text-${color}-500`,
    `dark:text-${color}-800`,
    `dark:text-white`,
    `border-${color}-500`
]);