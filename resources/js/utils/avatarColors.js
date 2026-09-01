const avatarColors = [
    'bg-red-500 text-white',
    'bg-blue-500 text-white',
    'bg-green-500 text-white',
    'bg-yellow-500 text-white',
    'bg-purple-500 text-white',
]

const randomAvatarColor = (name) => {
    if (!name || typeof name !== 'string') {
        return avatarColors[0]
    }

    let hash = 0
    for (const n of name) {
        hash += n.charCodeAt(0)
    }
    return avatarColors[hash % avatarColors.length]
}

export default randomAvatarColor