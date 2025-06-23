@extends('layouts.guest')

@section('content')
<div class="min-h-screen bg-gradient-to-br from-blue-100 to-indigo-200 dark:from-gray-900 dark:to-gray-800 flex items-center justify-center">
    <div class="max-w-md w-full space-y-8 p-8">
        <div class="text-center">
            <h1 class="text-4xl font-bold text-gray-900 dark:text-white mb-2">
                🎯 Humpday Headshots
            </h1>
            <p class="text-lg text-gray-600 dark:text-gray-300 mb-8">
                Welcome to your Laravel project with Tailwind CSS!
            </p>
        </div>

        <!-- Test Tailwind Components -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg p-6 space-y-4">
            <h2 class="text-2xl font-semibold text-gray-800 dark:text-white">
                Tailwind CSS Test
            </h2>
            
            <!-- Buttons -->
            <div class="flex space-x-3">
                <button class="px-4 py-2 bg-blue-500 text-white rounded-md hover:bg-blue-600 transition-colors">
                    Primary Button
                </button>
                <button class="px-4 py-2 bg-gray-500 text-white rounded-md hover:bg-gray-600 transition-colors">
                    Secondary Button
                </button>
            </div>

            <!-- Cards -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div class="p-4 bg-green-50 dark:bg-green-900 rounded-lg border border-green-200 dark:border-green-700">
                    <h3 class="font-medium text-green-800 dark:text-green-200">Success Card</h3>
                    <p class="text-sm text-green-600 dark:text-green-300">Tailwind CSS is working!</p>
                </div>
                <div class="p-4 bg-yellow-50 dark:bg-yellow-900 rounded-lg border border-yellow-200 dark:border-yellow-700">
                    <h3 class="font-medium text-yellow-800 dark:text-yellow-200">Info Card</h3>
                    <p class="text-sm text-yellow-600 dark:text-yellow-300">Ready for development</p>
                </div>
            </div>

            <!-- Form Elements -->
            <div class="space-y-3">
                <input 
                    type="text" 
                    placeholder="Test input field"
                    class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-white"
                >
                <select class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-white">
                    <option>Test dropdown</option>
                    <option>Option 1</option>
                    <option>Option 2</option>
                </select>
            </div>

            <!-- Responsive Grid -->
            <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-3">
                <div class="h-12 bg-red-200 dark:bg-red-700 rounded"></div>
                <div class="h-12 bg-blue-200 dark:bg-blue-700 rounded"></div>
                <div class="h-12 bg-green-200 dark:bg-green-700 rounded"></div>
                <div class="h-12 bg-purple-200 dark:bg-purple-700 rounded"></div>
            </div>
        </div>

        <div class="text-center">
            <p class="text-sm text-gray-500 dark:text-gray-400">
                Built with Laravel & Tailwind CSS v4.0
            </p>
        </div>
    </div>
</div>
@endsection
