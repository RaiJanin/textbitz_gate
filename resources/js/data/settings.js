const settings = {
    preferences: {
        defaultCategory: 'Promo',
        notifications: 'Off',
        darkMode: 'System'
    },
    darkModeChoices: [
        'On',
        'Off',
        'System'
    ],
    message: {
        variables: [
            {
                key: 'name',
                label: 'CONTACT_NAME'
            },
            {
                key: 'business_name',
                label: 'BUSINESS_NAME'
            },
            {
                key: 'date',
                label: 'CURRENT_DATE'
            },
            {
                key: 'phone_number',
                label: 'CONTACT_PHONE_NUMBER'
            },
        ],
    },
}

export default settings