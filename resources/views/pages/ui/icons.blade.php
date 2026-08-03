@extends('layouts.main')
@section('title', 'Icons')
@section('content')
    <x-page-header title="{{ __('Icons') }}" subtitle="{{ __('lorem ipsum dolor sit amet, consectetur adipisicing elit') }}" icon="ik ik-command"
        :breadcrumbs="['Home' => route('dashboard'), 'UI' => '#', 'Icons' => null]" />

    <x-card>
        <x-slot:header>
            <span class="flex items-center justify-between gap-4">
                {{ __('Font Kit Icons') }}
                <a class="text-sm font-medium text-primary-600 hover:underline" target="_blank" href="http://iconkit.lavalite.org/">{{ __('IconKit') }}</a>
            </span>
        </x-slot:header>
        <div class="grid grid-cols-2 gap-5 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6">
<div class="flex flex-col items-center justify-center gap-2 rounded-lg border border-gray-100 p-4 text-center transition hover:bg-gray-50">
                <i class="ik ik-alert-octagon text-3xl text-primary-500"></i>
                <span class="text-sm font-medium text-gray-700">alert-octagon</span>
                <span class="text-xs text-gray-400">.ik-alert-octagon</span>
            </div>
            
                    <div class="flex flex-col items-center justify-center gap-2 rounded-lg border border-gray-100 p-4 text-center transition hover:bg-gray-50">
                <i class="ik ik-alert-circle text-3xl text-primary-500"></i>
                <span class="text-sm font-medium text-gray-700">alert-circle</span>
                <span class="text-xs text-gray-400">.ik-alert-circle</span>
            </div>
            

                  <div class="flex flex-col items-center justify-center gap-2 rounded-lg border border-gray-100 p-4 text-center transition hover:bg-gray-50">
                <i class="ik ik-activity text-3xl text-primary-500"></i>
                <span class="text-sm font-medium text-gray-700">activity</span>
                <span class="text-xs text-gray-400">.ik-activity</span>
            </div>
            

                  <div class="flex flex-col items-center justify-center gap-2 rounded-lg border border-gray-100 p-4 text-center transition hover:bg-gray-50">
                <i class="ik ik-alert-triangle text-3xl text-primary-500"></i>
                <span class="text-sm font-medium text-gray-700">alert-triangle</span>
                <span class="text-xs text-gray-400">.ik-alert-triangle</span>
            </div>
            

                  <div class="flex flex-col items-center justify-center gap-2 rounded-lg border border-gray-100 p-4 text-center transition hover:bg-gray-50">
                <i class="ik ik-align-center text-3xl text-primary-500"></i>
                <span class="text-sm font-medium text-gray-700">align-center</span>
                <span class="text-xs text-gray-400">.ik-align-center</span>
            </div>
            

                  <div class="flex flex-col items-center justify-center gap-2 rounded-lg border border-gray-100 p-4 text-center transition hover:bg-gray-50">
                <i class="ik ik-airplay text-3xl text-primary-500"></i>
                <span class="text-sm font-medium text-gray-700">airplay</span>
                <span class="text-xs text-gray-400">.ik-airplay</span>
            </div>
            

                  <div class="flex flex-col items-center justify-center gap-2 rounded-lg border border-gray-100 p-4 text-center transition hover:bg-gray-50">
                <i class="ik ik-align-justify text-3xl text-primary-500"></i>
                <span class="text-sm font-medium text-gray-700">align-justify</span>
                <span class="text-xs text-gray-400">.ik-align-justify</span>
            </div>
            

                  <div class="flex flex-col items-center justify-center gap-2 rounded-lg border border-gray-100 p-4 text-center transition hover:bg-gray-50">
                <i class="ik ik-align-left text-3xl text-primary-500"></i>
                <span class="text-sm font-medium text-gray-700">align-left</span>
                <span class="text-xs text-gray-400">.ik-align-left</span>
            </div>
            

                  <div class="flex flex-col items-center justify-center gap-2 rounded-lg border border-gray-100 p-4 text-center transition hover:bg-gray-50">
                <i class="ik ik-align-right text-3xl text-primary-500"></i>
                <span class="text-sm font-medium text-gray-700">align-right</span>
                <span class="text-xs text-gray-400">.ik-align-right</span>
            </div>
            

                  <div class="flex flex-col items-center justify-center gap-2 rounded-lg border border-gray-100 p-4 text-center transition hover:bg-gray-50">
                <i class="ik ik-arrow-down-left text-3xl text-primary-500"></i>
                <span class="text-sm font-medium text-gray-700">arrow-down-left</span>
                <span class="text-xs text-gray-400">.ik-arrow-down-left</span>
            </div>
            

                  <div class="flex flex-col items-center justify-center gap-2 rounded-lg border border-gray-100 p-4 text-center transition hover:bg-gray-50">
                <i class="ik ik-arrow-down-right text-3xl text-primary-500"></i>
                <span class="text-sm font-medium text-gray-700">arrow-down-right</span>
                <span class="text-xs text-gray-400">.ik-arrow-down-right</span>
            </div>
            

                  <div class="flex flex-col items-center justify-center gap-2 rounded-lg border border-gray-100 p-4 text-center transition hover:bg-gray-50">
                <i class="ik ik-anchor text-3xl text-primary-500"></i>
                <span class="text-sm font-medium text-gray-700">anchor</span>
                <span class="text-xs text-gray-400">.ik-anchor</span>
            </div>
            

                  <div class="flex flex-col items-center justify-center gap-2 rounded-lg border border-gray-100 p-4 text-center transition hover:bg-gray-50">
                <i class="ik ik-aperture text-3xl text-primary-500"></i>
                <span class="text-sm font-medium text-gray-700">aperture</span>
                <span class="text-xs text-gray-400">.ik-aperture</span>
            </div>
            

                  <div class="flex flex-col items-center justify-center gap-2 rounded-lg border border-gray-100 p-4 text-center transition hover:bg-gray-50">
                <i class="ik ik-arrow-left text-3xl text-primary-500"></i>
                <span class="text-sm font-medium text-gray-700">arrow-left</span>
                <span class="text-xs text-gray-400">.ik-arrow-left</span>
            </div>
            

                  <div class="flex flex-col items-center justify-center gap-2 rounded-lg border border-gray-100 p-4 text-center transition hover:bg-gray-50">
                <i class="ik ik-arrow-right text-3xl text-primary-500"></i>
                <span class="text-sm font-medium text-gray-700">arrow-right</span>
                <span class="text-xs text-gray-400">.ik-arrow-right</span>
            </div>
            

                  <div class="flex flex-col items-center justify-center gap-2 rounded-lg border border-gray-100 p-4 text-center transition hover:bg-gray-50">
                <i class="ik ik-arrow-down text-3xl text-primary-500"></i>
                <span class="text-sm font-medium text-gray-700">arrow-down</span>
                <span class="text-xs text-gray-400">.ik-arrow-down</span>
            </div>
            

                  <div class="flex flex-col items-center justify-center gap-2 rounded-lg border border-gray-100 p-4 text-center transition hover:bg-gray-50">
                <i class="ik ik-arrow-up-left text-3xl text-primary-500"></i>
                <span class="text-sm font-medium text-gray-700">arrow-up-left</span>
                <span class="text-xs text-gray-400">.ik-arrow-up-left</span>
            </div>
            

                  <div class="flex flex-col items-center justify-center gap-2 rounded-lg border border-gray-100 p-4 text-center transition hover:bg-gray-50">
                <i class="ik ik-arrow-up-right text-3xl text-primary-500"></i>
                <span class="text-sm font-medium text-gray-700">arrow-up-right</span>
                <span class="text-xs text-gray-400">.ik-arrow-up-right</span>
            </div>
            

                  <div class="flex flex-col items-center justify-center gap-2 rounded-lg border border-gray-100 p-4 text-center transition hover:bg-gray-50">
                <i class="ik ik-arrow-up text-3xl text-primary-500"></i>
                <span class="text-sm font-medium text-gray-700">arrow-up</span>
                <span class="text-xs text-gray-400">.ik-arrow-up</span>
            </div>
            

                  <div class="flex flex-col items-center justify-center gap-2 rounded-lg border border-gray-100 p-4 text-center transition hover:bg-gray-50">
                <i class="ik ik-award text-3xl text-primary-500"></i>
                <span class="text-sm font-medium text-gray-700">award</span>
                <span class="text-xs text-gray-400">.ik-award</span>
            </div>
            

                  <div class="flex flex-col items-center justify-center gap-2 rounded-lg border border-gray-100 p-4 text-center transition hover:bg-gray-50">
                <i class="ik ik-bar-chart text-3xl text-primary-500"></i>
                <span class="text-sm font-medium text-gray-700">bar-chart</span>
                <span class="text-xs text-gray-400">.ik-bar-chart</span>
            </div>
            

                  <div class="flex flex-col items-center justify-center gap-2 rounded-lg border border-gray-100 p-4 text-center transition hover:bg-gray-50">
                <i class="ik ik-at-sign text-3xl text-primary-500"></i>
                <span class="text-sm font-medium text-gray-700">at-sign</span>
                <span class="text-xs text-gray-400">.ik-at-sign</span>
            </div>
            

                  <div class="flex flex-col items-center justify-center gap-2 rounded-lg border border-gray-100 p-4 text-center transition hover:bg-gray-50">
                <i class="ik ik-bar-chart-2 text-3xl text-primary-500"></i>
                <span class="text-sm font-medium text-gray-700">bar-chart-2</span>
                <span class="text-xs text-gray-400">.ik-bar-chart-</span>
            </div>
            

                  <div class="flex flex-col items-center justify-center gap-2 rounded-lg border border-gray-100 p-4 text-center transition hover:bg-gray-50">
                <i class="ik ik-battery-charging text-3xl text-primary-500"></i>
                <span class="text-sm font-medium text-gray-700">battery-charging</span>
                <span class="text-xs text-gray-400">.ik-battery-charging</span>
            </div>
            

                  <div class="flex flex-col items-center justify-center gap-2 rounded-lg border border-gray-100 p-4 text-center transition hover:bg-gray-50">
                <i class="ik ik-bell-off text-3xl text-primary-500"></i>
                <span class="text-sm font-medium text-gray-700">bell-off</span>
                <span class="text-xs text-gray-400">.ik-bell-off</span>
            </div>
            

                  <div class="flex flex-col items-center justify-center gap-2 rounded-lg border border-gray-100 p-4 text-center transition hover:bg-gray-50">
                <i class="ik ik-battery text-3xl text-primary-500"></i>
                <span class="text-sm font-medium text-gray-700">battery</span>
                <span class="text-xs text-gray-400">.ik-battery</span>
            </div>
            

                  <div class="flex flex-col items-center justify-center gap-2 rounded-lg border border-gray-100 p-4 text-center transition hover:bg-gray-50">
                <i class="ik ik-bluetooth text-3xl text-primary-500"></i>
                <span class="text-sm font-medium text-gray-700">bluetooth</span>
                <span class="text-xs text-gray-400">.ik-bluetooth</span>
            </div>
            

                  <div class="flex flex-col items-center justify-center gap-2 rounded-lg border border-gray-100 p-4 text-center transition hover:bg-gray-50">
                <i class="ik ik-bell text-3xl text-primary-500"></i>
                <span class="text-sm font-medium text-gray-700">bell</span>
                <span class="text-xs text-gray-400">.ik-bell</span>
            </div>
            

                  <div class="flex flex-col items-center justify-center gap-2 rounded-lg border border-gray-100 p-4 text-center transition hover:bg-gray-50">
                <i class="ik ik-book text-3xl text-primary-500"></i>
                <span class="text-sm font-medium text-gray-700">book</span>
                <span class="text-xs text-gray-400">.ik-book</span>
            </div>
            

                  <div class="flex flex-col items-center justify-center gap-2 rounded-lg border border-gray-100 p-4 text-center transition hover:bg-gray-50">
                <i class="ik ik-briefcase text-3xl text-primary-500"></i>
                <span class="text-sm font-medium text-gray-700">briefcase</span>
                <span class="text-xs text-gray-400">.ik-briefcase</span>
            </div>
            

                  <div class="flex flex-col items-center justify-center gap-2 rounded-lg border border-gray-100 p-4 text-center transition hover:bg-gray-50">
                <i class="ik ik-camera-off text-3xl text-primary-500"></i>
                <span class="text-sm font-medium text-gray-700">camera-off</span>
                <span class="text-xs text-gray-400">.ik-camera-off</span>
            </div>
            

                  <div class="flex flex-col items-center justify-center gap-2 rounded-lg border border-gray-100 p-4 text-center transition hover:bg-gray-50">
                <i class="ik ik-calendar text-3xl text-primary-500"></i>
                <span class="text-sm font-medium text-gray-700">calendar</span>
                <span class="text-xs text-gray-400">.ik-calendar</span>
            </div>
            

                  <div class="flex flex-col items-center justify-center gap-2 rounded-lg border border-gray-100 p-4 text-center transition hover:bg-gray-50">
                <i class="ik ik-bookmark text-3xl text-primary-500"></i>
                <span class="text-sm font-medium text-gray-700">bookmark</span>
                <span class="text-xs text-gray-400">.ik-bookmark</span>
            </div>
            

                  <div class="flex flex-col items-center justify-center gap-2 rounded-lg border border-gray-100 p-4 text-center transition hover:bg-gray-50">
                <i class="ik ik-box text-3xl text-primary-500"></i>
                <span class="text-sm font-medium text-gray-700">box</span>
                <span class="text-xs text-gray-400">.ik-box</span>
            </div>
            

                  <div class="flex flex-col items-center justify-center gap-2 rounded-lg border border-gray-100 p-4 text-center transition hover:bg-gray-50">
                <i class="ik ik-camera text-3xl text-primary-500"></i>
                <span class="text-sm font-medium text-gray-700">camera</span>
                <span class="text-xs text-gray-400">.ik-camera</span>
            </div>
            

                  <div class="flex flex-col items-center justify-center gap-2 rounded-lg border border-gray-100 p-4 text-center transition hover:bg-gray-50">
                <i class="ik ik-check-circle text-3xl text-primary-500"></i>
                <span class="text-sm font-medium text-gray-700">check-circle</span>
                <span class="text-xs text-gray-400">.ik-check-circle</span>
            </div>
            

                  <div class="flex flex-col items-center justify-center gap-2 rounded-lg border border-gray-100 p-4 text-center transition hover:bg-gray-50">
                <i class="ik ik-check text-3xl text-primary-500"></i>
                <span class="text-sm font-medium text-gray-700">check</span>
                <span class="text-xs text-gray-400">.ik-check</span>
            </div>
            

                  <div class="flex flex-col items-center justify-center gap-2 rounded-lg border border-gray-100 p-4 text-center transition hover:bg-gray-50">
                <i class="ik ik-check-square text-3xl text-primary-500"></i>
                <span class="text-sm font-medium text-gray-700">check-square</span>
                <span class="text-xs text-gray-400">.ik-check-square</span>
            </div>
            

                  <div class="flex flex-col items-center justify-center gap-2 rounded-lg border border-gray-100 p-4 text-center transition hover:bg-gray-50">
                <i class="ik ik-cast text-3xl text-primary-500"></i>
                <span class="text-sm font-medium text-gray-700">cast</span>
                <span class="text-xs text-gray-400">.ik-cast</span>
            </div>
            

                  <div class="flex flex-col items-center justify-center gap-2 rounded-lg border border-gray-100 p-4 text-center transition hover:bg-gray-50">
                <i class="ik ik-chevron-down text-3xl text-primary-500"></i>
                <span class="text-sm font-medium text-gray-700">chevron-down</span>
                <span class="text-xs text-gray-400">.ik-chevron-down</span>
            </div>
            

                  <div class="flex flex-col items-center justify-center gap-2 rounded-lg border border-gray-100 p-4 text-center transition hover:bg-gray-50">
                <i class="ik ik-chevron-left text-3xl text-primary-500"></i>
                <span class="text-sm font-medium text-gray-700">chevron-left</span>
                <span class="text-xs text-gray-400">.ik-chevron-left</span>
            </div>
            

                  <div class="flex flex-col items-center justify-center gap-2 rounded-lg border border-gray-100 p-4 text-center transition hover:bg-gray-50">
                <i class="ik ik-chevron-right text-3xl text-primary-500"></i>
                <span class="text-sm font-medium text-gray-700">chevron-right</span>
                <span class="text-xs text-gray-400">.ik-chevron-right</span>
            </div>
            

                  <div class="flex flex-col items-center justify-center gap-2 rounded-lg border border-gray-100 p-4 text-center transition hover:bg-gray-50">
                <i class="ik ik-chevron-up text-3xl text-primary-500"></i>
                <span class="text-sm font-medium text-gray-700">chevron-up</span>
                <span class="text-xs text-gray-400">.ik-chevron-up</span>
            </div>
            

                  <div class="flex flex-col items-center justify-center gap-2 rounded-lg border border-gray-100 p-4 text-center transition hover:bg-gray-50">
                <i class="ik ik-chevrons-down text-3xl text-primary-500"></i>
                <span class="text-sm font-medium text-gray-700">chevrons-down</span>
                <span class="text-xs text-gray-400">.ik-chevrons-down</span>
            </div>
            

                  <div class="flex flex-col items-center justify-center gap-2 rounded-lg border border-gray-100 p-4 text-center transition hover:bg-gray-50">
                <i class="ik ik-chevrons-right text-3xl text-primary-500"></i>
                <span class="text-sm font-medium text-gray-700">chevrons-right</span>
                <span class="text-xs text-gray-400">.ik-chevrons-right</span>
            </div>
            

                  <div class="flex flex-col items-center justify-center gap-2 rounded-lg border border-gray-100 p-4 text-center transition hover:bg-gray-50">
                <i class="ik ik-chevrons-up text-3xl text-primary-500"></i>
                <span class="text-sm font-medium text-gray-700">chevrons-up</span>
                <span class="text-xs text-gray-400">.ik-chevrons-up</span>
            </div>
            

                  <div class="flex flex-col items-center justify-center gap-2 rounded-lg border border-gray-100 p-4 text-center transition hover:bg-gray-50">
                <i class="ik ik-chevrons-left text-3xl text-primary-500"></i>
                <span class="text-sm font-medium text-gray-700">chevrons-left</span>
                <span class="text-xs text-gray-400">.ik-chevrons-left</span>
            </div>
            

                  <div class="flex flex-col items-center justify-center gap-2 rounded-lg border border-gray-100 p-4 text-center transition hover:bg-gray-50">
                <i class="ik ik-circle text-3xl text-primary-500"></i>
                <span class="text-sm font-medium text-gray-700">circle</span>
                <span class="text-xs text-gray-400">.ik-circle</span>
            </div>
            

                  <div class="flex flex-col items-center justify-center gap-2 rounded-lg border border-gray-100 p-4 text-center transition hover:bg-gray-50">
                <i class="ik ik-clipboard text-3xl text-primary-500"></i>
                <span class="text-sm font-medium text-gray-700">clipboard</span>
                <span class="text-xs text-gray-400">.ik-clipboard</span>
            </div>
            

                  <div class="flex flex-col items-center justify-center gap-2 rounded-lg border border-gray-100 p-4 text-center transition hover:bg-gray-50">
                <i class="ik ik-chrome text-3xl text-primary-500"></i>
                <span class="text-sm font-medium text-gray-700">chrome</span>
                <span class="text-xs text-gray-400">.ik-chrome</span>
            </div>
            

                  <div class="flex flex-col items-center justify-center gap-2 rounded-lg border border-gray-100 p-4 text-center transition hover:bg-gray-50">
                <i class="ik ik-clock text-3xl text-primary-500"></i>
                <span class="text-sm font-medium text-gray-700">clock</span>
                <span class="text-xs text-gray-400">.ik-clock</span>
            </div>
            

                  <div class="flex flex-col items-center justify-center gap-2 rounded-lg border border-gray-100 p-4 text-center transition hover:bg-gray-50">
                <i class="ik ik-cloud-lightning text-3xl text-primary-500"></i>
                <span class="text-sm font-medium text-gray-700">cloud-lightning</span>
                <span class="text-xs text-gray-400">.ik-cloud-lightning</span>
            </div>
            

                  <div class="flex flex-col items-center justify-center gap-2 rounded-lg border border-gray-100 p-4 text-center transition hover:bg-gray-50">
                <i class="ik ik-cloud-drizzle text-3xl text-primary-500"></i>
                <span class="text-sm font-medium text-gray-700">cloud-drizzle</span>
                <span class="text-xs text-gray-400">.ik-cloud-drizzle</span>
            </div>
            

                  <div class="flex flex-col items-center justify-center gap-2 rounded-lg border border-gray-100 p-4 text-center transition hover:bg-gray-50">
                <i class="ik ik-cloud-rain text-3xl text-primary-500"></i>
                <span class="text-sm font-medium text-gray-700">cloud-rain</span>
                <span class="text-xs text-gray-400">.ik-cloud-rain</span>
            </div>
            

                  <div class="flex flex-col items-center justify-center gap-2 rounded-lg border border-gray-100 p-4 text-center transition hover:bg-gray-50">
                <i class="ik ik-cloud-off text-3xl text-primary-500"></i>
                <span class="text-sm font-medium text-gray-700">cloud-off</span>
                <span class="text-xs text-gray-400">.ik-cloud-off</span>
            </div>
            

                  <div class="flex flex-col items-center justify-center gap-2 rounded-lg border border-gray-100 p-4 text-center transition hover:bg-gray-50">
                <i class="ik ik-codepen text-3xl text-primary-500"></i>
                <span class="text-sm font-medium text-gray-700">codepen</span>
                <span class="text-xs text-gray-400">.ik-codepen</span>
            </div>
            

                  <div class="flex flex-col items-center justify-center gap-2 rounded-lg border border-gray-100 p-4 text-center transition hover:bg-gray-50">
                <i class="ik ik-cloud-snow text-3xl text-primary-500"></i>
                <span class="text-sm font-medium text-gray-700">cloud-snow</span>
                <span class="text-xs text-gray-400">.ik-cloud-snow</span>
            </div>
            

                  <div class="flex flex-col items-center justify-center gap-2 rounded-lg border border-gray-100 p-4 text-center transition hover:bg-gray-50">
                <i class="ik ik-compass text-3xl text-primary-500"></i>
                <span class="text-sm font-medium text-gray-700">compass</span>
                <span class="text-xs text-gray-400">.ik-compass</span>
            </div>
            

                  <div class="flex flex-col items-center justify-center gap-2 rounded-lg border border-gray-100 p-4 text-center transition hover:bg-gray-50">
                <i class="ik ik-copy text-3xl text-primary-500"></i>
                <span class="text-sm font-medium text-gray-700">copy</span>
                <span class="text-xs text-gray-400">.ik-copy</span>
            </div>
            

                  <div class="flex flex-col items-center justify-center gap-2 rounded-lg border border-gray-100 p-4 text-center transition hover:bg-gray-50">
                <i class="ik ik-corner-down-right text-3xl text-primary-500"></i>
                <span class="text-sm font-medium text-gray-700">corner-down-right</span>
                <span class="text-xs text-gray-400">.ik-corner-down-right</span>
            </div>
            

                  <div class="flex flex-col items-center justify-center gap-2 rounded-lg border border-gray-100 p-4 text-center transition hover:bg-gray-50">
                <i class="ik ik-corner-down-left text-3xl text-primary-500"></i>
                <span class="text-sm font-medium text-gray-700">corner-down-left</span>
                <span class="text-xs text-gray-400">.ik-corner-down-left</span>
            </div>
            

                  <div class="flex flex-col items-center justify-center gap-2 rounded-lg border border-gray-100 p-4 text-center transition hover:bg-gray-50">
                <i class="ik ik-corner-left-down text-3xl text-primary-500"></i>
                <span class="text-sm font-medium text-gray-700">corner-left-down</span>
                <span class="text-xs text-gray-400">.ik-corner-left-down</span>
            </div>
            

                  <div class="flex flex-col items-center justify-center gap-2 rounded-lg border border-gray-100 p-4 text-center transition hover:bg-gray-50">
                <i class="ik ik-corner-left-up text-3xl text-primary-500"></i>
                <span class="text-sm font-medium text-gray-700">corner-left-up</span>
                <span class="text-xs text-gray-400">.ik-corner-left-up</span>
            </div>
            

                  <div class="flex flex-col items-center justify-center gap-2 rounded-lg border border-gray-100 p-4 text-center transition hover:bg-gray-50">
                <i class="ik ik-corner-up-left text-3xl text-primary-500"></i>
                <span class="text-sm font-medium text-gray-700">corner-up-left</span>
                <span class="text-xs text-gray-400">.ik-corner-up-left</span>
            </div>
            

                  <div class="flex flex-col items-center justify-center gap-2 rounded-lg border border-gray-100 p-4 text-center transition hover:bg-gray-50">
                <i class="ik ik-corner-up-right text-3xl text-primary-500"></i>
                <span class="text-sm font-medium text-gray-700">corner-up-right</span>
                <span class="text-xs text-gray-400">.ik-corner-up-right</span>
            </div>
            

                  <div class="flex flex-col items-center justify-center gap-2 rounded-lg border border-gray-100 p-4 text-center transition hover:bg-gray-50">
                <i class="ik ik-corner-right-down text-3xl text-primary-500"></i>
                <span class="text-sm font-medium text-gray-700">corner-right-down</span>
                <span class="text-xs text-gray-400">.ik-corner-right-down</span>
            </div>
            

                  <div class="flex flex-col items-center justify-center gap-2 rounded-lg border border-gray-100 p-4 text-center transition hover:bg-gray-50">
                <i class="ik ik-corner-right-up text-3xl text-primary-500"></i>
                <span class="text-sm font-medium text-gray-700">corner-right-up</span>
                <span class="text-xs text-gray-400">.ik-corner-right-up</span>
            </div>
            

                  <div class="flex flex-col items-center justify-center gap-2 rounded-lg border border-gray-100 p-4 text-center transition hover:bg-gray-50">
                <i class="ik ik-cpu text-3xl text-primary-500"></i>
                <span class="text-sm font-medium text-gray-700">cpu</span>
                <span class="text-xs text-gray-400">.ik-cpu</span>
            </div>
            

                  <div class="flex flex-col items-center justify-center gap-2 rounded-lg border border-gray-100 p-4 text-center transition hover:bg-gray-50">
                <i class="ik ik-credit-card text-3xl text-primary-500"></i>
                <span class="text-sm font-medium text-gray-700">credit-card</span>
                <span class="text-xs text-gray-400">.ik-credit-card</span>
            </div>
            

                  <div class="flex flex-col items-center justify-center gap-2 rounded-lg border border-gray-100 p-4 text-center transition hover:bg-gray-50">
                <i class="ik ik-crosshair text-3xl text-primary-500"></i>
                <span class="text-sm font-medium text-gray-700">crosshair</span>
                <span class="text-xs text-gray-400">.ik-crosshair</span>
            </div>
            

                  <div class="flex flex-col items-center justify-center gap-2 rounded-lg border border-gray-100 p-4 text-center transition hover:bg-gray-50">
                <i class="ik ik-disc text-3xl text-primary-500"></i>
                <span class="text-sm font-medium text-gray-700">disc</span>
                <span class="text-xs text-gray-400">.ik-disc</span>
            </div>
            

                  <div class="flex flex-col items-center justify-center gap-2 rounded-lg border border-gray-100 p-4 text-center transition hover:bg-gray-50">
                <i class="ik ik-delete text-3xl text-primary-500"></i>
                <span class="text-sm font-medium text-gray-700">delete</span>
                <span class="text-xs text-gray-400">.ik-delete</span>
            </div>
            

                  <div class="flex flex-col items-center justify-center gap-2 rounded-lg border border-gray-100 p-4 text-center transition hover:bg-gray-50">
                <i class="ik ik-download-cloud text-3xl text-primary-500"></i>
                <span class="text-sm font-medium text-gray-700">download-cloud</span>
                <span class="text-xs text-gray-400">.ik-download-cloud</span>
            </div>
            

                  <div class="flex flex-col items-center justify-center gap-2 rounded-lg border border-gray-100 p-4 text-center transition hover:bg-gray-50">
                <i class="ik ik-download text-3xl text-primary-500"></i>
                <span class="text-sm font-medium text-gray-700">download</span>
                <span class="text-xs text-gray-400">.ik-download</span>
            </div>
            

                  <div class="flex flex-col items-center justify-center gap-2 rounded-lg border border-gray-100 p-4 text-center transition hover:bg-gray-50">
                <i class="ik ik-droplet text-3xl text-primary-500"></i>
                <span class="text-sm font-medium text-gray-700">droplet</span>
                <span class="text-xs text-gray-400">.ik-droplet</span>
            </div>
            

                  <div class="flex flex-col items-center justify-center gap-2 rounded-lg border border-gray-100 p-4 text-center transition hover:bg-gray-50">
                <i class="ik ik-edit-2 text-3xl text-primary-500"></i>
                <span class="text-sm font-medium text-gray-700">edit-2</span>
                <span class="text-xs text-gray-400">.ik-edit-</span>
            </div>
            

                  <div class="flex flex-col items-center justify-center gap-2 rounded-lg border border-gray-100 p-4 text-center transition hover:bg-gray-50">
                <i class="ik ik-edit text-3xl text-primary-500"></i>
                <span class="text-sm font-medium text-gray-700">edit</span>
                <span class="text-xs text-gray-400">.ik-edit</span>
            </div>
            

                  <div class="flex flex-col items-center justify-center gap-2 rounded-lg border border-gray-100 p-4 text-center transition hover:bg-gray-50">
                <i class="ik ik-edit-1 text-3xl text-primary-500"></i>
                <span class="text-sm font-medium text-gray-700">edit-3</span>
                <span class="text-xs text-gray-400">.ik-edit-1</span>
            </div>
            

                  <div class="flex flex-col items-center justify-center gap-2 rounded-lg border border-gray-100 p-4 text-center transition hover:bg-gray-50">
                <i class="ik ik-external-link text-3xl text-primary-500"></i>
                <span class="text-sm font-medium text-gray-700">external-link</span>
                <span class="text-xs text-gray-400">.ik-external-link</span>
            </div>
            

                  <div class="flex flex-col items-center justify-center gap-2 rounded-lg border border-gray-100 p-4 text-center transition hover:bg-gray-50">
                <i class="ik ik-eye text-3xl text-primary-500"></i>
                <span class="text-sm font-medium text-gray-700">eye</span>
                <span class="text-xs text-gray-400">.ik-eye</span>
            </div>
            

                  <div class="flex flex-col items-center justify-center gap-2 rounded-lg border border-gray-100 p-4 text-center transition hover:bg-gray-50">
                <i class="ik ik-feather text-3xl text-primary-500"></i>
                <span class="text-sm font-medium text-gray-700">feather</span>
                <span class="text-xs text-gray-400">.ik-feather</span>
            </div>
            

                  <div class="flex flex-col items-center justify-center gap-2 rounded-lg border border-gray-100 p-4 text-center transition hover:bg-gray-50">
                <i class="ik ik-facebook text-3xl text-primary-500"></i>
                <span class="text-sm font-medium text-gray-700">facebook</span>
                <span class="text-xs text-gray-400">.ik-facebook</span>
            </div>
            

                  <div class="flex flex-col items-center justify-center gap-2 rounded-lg border border-gray-100 p-4 text-center transition hover:bg-gray-50">
                <i class="ik ik-file-minus text-3xl text-primary-500"></i>
                <span class="text-sm font-medium text-gray-700">file-minus</span>
                <span class="text-xs text-gray-400">.ik-file-minus</span>
            </div>
            

                  <div class="flex flex-col items-center justify-center gap-2 rounded-lg border border-gray-100 p-4 text-center transition hover:bg-gray-50">
                <i class="ik ik-eye-off text-3xl text-primary-500"></i>
                <span class="text-sm font-medium text-gray-700">eye-off</span>
                <span class="text-xs text-gray-400">.ik-eye-off</span>
            </div>
            

                  <div class="flex flex-col items-center justify-center gap-2 rounded-lg border border-gray-100 p-4 text-center transition hover:bg-gray-50">
                <i class="ik ik-fast-forward text-3xl text-primary-500"></i>
                <span class="text-sm font-medium text-gray-700">fast-forward</span>
                <span class="text-xs text-gray-400">.ik-fast-forward</span>
            </div>
            

                  <div class="flex flex-col items-center justify-center gap-2 rounded-lg border border-gray-100 p-4 text-center transition hover:bg-gray-50">
                <i class="ik ik-file-text text-3xl text-primary-500"></i>
                <span class="text-sm font-medium text-gray-700">file-text</span>
                <span class="text-xs text-gray-400">.ik-file-text</span>
            </div>
            

                  <div class="flex flex-col items-center justify-center gap-2 rounded-lg border border-gray-100 p-4 text-center transition hover:bg-gray-50">
                <i class="ik ik-film text-3xl text-primary-500"></i>
                <span class="text-sm font-medium text-gray-700">film</span>
                <span class="text-xs text-gray-400">.ik-film</span>
            </div>
            

                  <div class="flex flex-col items-center justify-center gap-2 rounded-lg border border-gray-100 p-4 text-center transition hover:bg-gray-50">
                <i class="ik ik-file text-3xl text-primary-500"></i>
                <span class="text-sm font-medium text-gray-700">file</span>
                <span class="text-xs text-gray-400">.ik-file</span>
            </div>
            

                  <div class="flex flex-col items-center justify-center gap-2 rounded-lg border border-gray-100 p-4 text-center transition hover:bg-gray-50">
                <i class="ik ik-file-plus text-3xl text-primary-500"></i>
                <span class="text-sm font-medium text-gray-700">file-plus</span>
                <span class="text-xs text-gray-400">.ik-file-plus</span>
            </div>
            

                  <div class="flex flex-col items-center justify-center gap-2 rounded-lg border border-gray-100 p-4 text-center transition hover:bg-gray-50">
                <i class="ik ik-folder text-3xl text-primary-500"></i>
                <span class="text-sm font-medium text-gray-700">folder</span>
                <span class="text-xs text-gray-400">.ik-folder</span>
            </div>
            

                  <div class="flex flex-col items-center justify-center gap-2 rounded-lg border border-gray-100 p-4 text-center transition hover:bg-gray-50">
                <i class="ik ik-filter text-3xl text-primary-500"></i>
                <span class="text-sm font-medium text-gray-700">filter</span>
                <span class="text-xs text-gray-400">.ik-filter</span>
            </div>
            

                  <div class="flex flex-col items-center justify-center gap-2 rounded-lg border border-gray-100 p-4 text-center transition hover:bg-gray-50">
                <i class="ik ik-flag text-3xl text-primary-500"></i>
                <span class="text-sm font-medium text-gray-700">flag</span>
                <span class="text-xs text-gray-400">.ik-flag</span>
            </div>
            

                  <div class="flex flex-col items-center justify-center gap-2 rounded-lg border border-gray-100 p-4 text-center transition hover:bg-gray-50">
                <i class="ik ik-globe text-3xl text-primary-500"></i>
                <span class="text-sm font-medium text-gray-700">globe</span>
                <span class="text-xs text-gray-400">.ik-globe</span>
            </div>
            

                  <div class="flex flex-col items-center justify-center gap-2 rounded-lg border border-gray-100 p-4 text-center transition hover:bg-gray-50">
                <i class="ik ik-grid text-3xl text-primary-500"></i>
                <span class="text-sm font-medium text-gray-700">grid</span>
                <span class="text-xs text-gray-400">.ik-grid</span>
            </div>
            

                  <div class="flex flex-col items-center justify-center gap-2 rounded-lg border border-gray-100 p-4 text-center transition hover:bg-gray-50">
                <i class="ik ik-heart text-3xl text-primary-500"></i>
                <span class="text-sm font-medium text-gray-700">heart</span>
                <span class="text-xs text-gray-400">.ik-heart</span>
            </div>
            

                  <div class="flex flex-col items-center justify-center gap-2 rounded-lg border border-gray-100 p-4 text-center transition hover:bg-gray-50">
                <i class="ik ik-home text-3xl text-primary-500"></i>
                <span class="text-sm font-medium text-gray-700">home</span>
                <span class="text-xs text-gray-400">.ik-home</span>
            </div>
            

                  <div class="flex flex-col items-center justify-center gap-2 rounded-lg border border-gray-100 p-4 text-center transition hover:bg-gray-50">
                <i class="ik ik-github text-3xl text-primary-500"></i>
                <span class="text-sm font-medium text-gray-700">github</span>
                <span class="text-xs text-gray-400">.ik-github</span>
            </div>
            

                  <div class="flex flex-col items-center justify-center gap-2 rounded-lg border border-gray-100 p-4 text-center transition hover:bg-gray-50">
                <i class="ik ik-image text-3xl text-primary-500"></i>
                <span class="text-sm font-medium text-gray-700">image</span>
                <span class="text-xs text-gray-400">.ik-image</span>
            </div>
            

                  <div class="flex flex-col items-center justify-center gap-2 rounded-lg border border-gray-100 p-4 text-center transition hover:bg-gray-50">
                <i class="ik ik-inbox text-3xl text-primary-500"></i>
                <span class="text-sm font-medium text-gray-700">inbox</span>
                <span class="text-xs text-gray-400">.ik-inbox</span>
            </div>
            

                  <div class="flex flex-col items-center justify-center gap-2 rounded-lg border border-gray-100 p-4 text-center transition hover:bg-gray-50">
                <i class="ik ik-layers text-3xl text-primary-500"></i>
                <span class="text-sm font-medium text-gray-700">layers</span>
                <span class="text-xs text-gray-400">.ik-layers</span>
            </div>
            

                  <div class="flex flex-col items-center justify-center gap-2 rounded-lg border border-gray-100 p-4 text-center transition hover:bg-gray-50">
                <i class="ik ik-info text-3xl text-primary-500"></i>
                <span class="text-sm font-medium text-gray-700">info</span>
                <span class="text-xs text-gray-400">.ik-info</span>
            </div>
            

                  <div class="flex flex-col items-center justify-center gap-2 rounded-lg border border-gray-100 p-4 text-center transition hover:bg-gray-50">
                <i class="ik ik-instagram text-3xl text-primary-500"></i>
                <span class="text-sm font-medium text-gray-700">instagram</span>
                <span class="text-xs text-gray-400">.ik-instagram</span>
            </div>
            

                  <div class="flex flex-col items-center justify-center gap-2 rounded-lg border border-gray-100 p-4 text-center transition hover:bg-gray-50">
                <i class="ik ik-layout text-3xl text-primary-500"></i>
                <span class="text-sm font-medium text-gray-700">layout</span>
                <span class="text-xs text-gray-400">.ik-layout</span>
            </div>
            

                  <div class="flex flex-col items-center justify-center gap-2 rounded-lg border border-gray-100 p-4 text-center transition hover:bg-gray-50">
                <i class="ik ik-link-2 text-3xl text-primary-500"></i>
                <span class="text-sm font-medium text-gray-700">link-2</span>
                <span class="text-xs text-gray-400">.ik-link-</span>
            </div>
            

                  <div class="flex flex-col items-center justify-center gap-2 rounded-lg border border-gray-100 p-4 text-center transition hover:bg-gray-50">
                <i class="ik ik-life-buoy text-3xl text-primary-500"></i>
                <span class="text-sm font-medium text-gray-700">life-buoy</span>
                <span class="text-xs text-gray-400">.ik-life-buoy</span>
            </div>
            

                  <div class="flex flex-col items-center justify-center gap-2 rounded-lg border border-gray-100 p-4 text-center transition hover:bg-gray-50">
                <i class="ik ik-link text-3xl text-primary-500"></i>
                <span class="text-sm font-medium text-gray-700">link</span>
                <span class="text-xs text-gray-400">.ik-link</span>
            </div>
            

                  <div class="flex flex-col items-center justify-center gap-2 rounded-lg border border-gray-100 p-4 text-center transition hover:bg-gray-50">
                <i class="ik ik-log-in text-3xl text-primary-500"></i>
                <span class="text-sm font-medium text-gray-700">log-in</span>
                <span class="text-xs text-gray-400">.ik-log-in</span>
            </div>
            

                  <div class="flex flex-col items-center justify-center gap-2 rounded-lg border border-gray-100 p-4 text-center transition hover:bg-gray-50">
                <i class="ik ik-list text-3xl text-primary-500"></i>
                <span class="text-sm font-medium text-gray-700">list</span>
                <span class="text-xs text-gray-400">.ik-list</span>
            </div>
            

                  <div class="flex flex-col items-center justify-center gap-2 rounded-lg border border-gray-100 p-4 text-center transition hover:bg-gray-50">
                <i class="ik ik-lock text-3xl text-primary-500"></i>
                <span class="text-sm font-medium text-gray-700">lock</span>
                <span class="text-xs text-gray-400">.ik-lock</span>
            </div>
            

                  <div class="flex flex-col items-center justify-center gap-2 rounded-lg border border-gray-100 p-4 text-center transition hover:bg-gray-50">
                <i class="ik ik-log-out text-3xl text-primary-500"></i>
                <span class="text-sm font-medium text-gray-700">log-out</span>
                <span class="text-xs text-gray-400">.ik-log-out</span>
            </div>
            

                  <div class="flex flex-col items-center justify-center gap-2 rounded-lg border border-gray-100 p-4 text-center transition hover:bg-gray-50">
                <i class="ik ik-loader text-3xl text-primary-500"></i>
                <span class="text-sm font-medium text-gray-700">loader</span>
                <span class="text-xs text-gray-400">.ik-loader</span>
            </div>
            

                  <div class="flex flex-col items-center justify-center gap-2 rounded-lg border border-gray-100 p-4 text-center transition hover:bg-gray-50">
                <i class="ik ik-mail text-3xl text-primary-500"></i>
                <span class="text-sm font-medium text-gray-700">mail</span>
                <span class="text-xs text-gray-400">.ik-mail</span>
            </div>
            

                  <div class="flex flex-col items-center justify-center gap-2 rounded-lg border border-gray-100 p-4 text-center transition hover:bg-gray-50">
                <i class="ik ik-maximize-2 text-3xl text-primary-500"></i>
                <span class="text-sm font-medium text-gray-700">maximize-2</span>
                <span class="text-xs text-gray-400">.ik-maximize-2</span>
            </div>
            

                  <div class="flex flex-col items-center justify-center gap-2 rounded-lg border border-gray-100 p-4 text-center transition hover:bg-gray-50">
                <i class="ik ik-map text-3xl text-primary-500"></i>
                <span class="text-sm font-medium text-gray-700">map</span>
                <span class="text-xs text-gray-400">.ik-map</span>
            </div>
            

                  <div class="flex flex-col items-center justify-center gap-2 rounded-lg border border-gray-100 p-4 text-center transition hover:bg-gray-50">
                <i class="ik ik-maximize text-3xl text-primary-500"></i>
                <span class="text-sm font-medium text-gray-700">maximize</span>
                <span class="text-xs text-gray-400">.ik-maximize</span>
            </div>
            

                  <div class="flex flex-col items-center justify-center gap-2 rounded-lg border border-gray-100 p-4 text-center transition hover:bg-gray-50">
                <i class="ik ik-map-pin text-3xl text-primary-500"></i>
                <span class="text-sm font-medium text-gray-700">map-pin</span>
                <span class="text-xs text-gray-400">.ik-map-pin</span>
            </div>
            

                  <div class="flex flex-col items-center justify-center gap-2 rounded-lg border border-gray-100 p-4 text-center transition hover:bg-gray-50">
                <i class="ik ik-menu text-3xl text-primary-500"></i>
                <span class="text-sm font-medium text-gray-700">menu</span>
                <span class="text-xs text-gray-400">.ik-menu</span>
            </div>
            

                  <div class="flex flex-col items-center justify-center gap-2 rounded-lg border border-gray-100 p-4 text-center transition hover:bg-gray-50">
                <i class="ik ik-message-circle text-3xl text-primary-500"></i>
                <span class="text-sm font-medium text-gray-700">message-circle</span>
                <span class="text-xs text-gray-400">.ik-message-circle</span>
            </div>
            

                  <div class="flex flex-col items-center justify-center gap-2 rounded-lg border border-gray-100 p-4 text-center transition hover:bg-gray-50">
                <i class="ik ik-message-square text-3xl text-primary-500"></i>
                <span class="text-sm font-medium text-gray-700">message-square</span>
                <span class="text-xs text-gray-400">.ik-message-square</span>
            </div>
            

                  <div class="flex flex-col items-center justify-center gap-2 rounded-lg border border-gray-100 p-4 text-center transition hover:bg-gray-50">
                <i class="ik ik-minimize-2 text-3xl text-primary-500"></i>
                <span class="text-sm font-medium text-gray-700">minimize-2</span>
                <span class="text-xs text-gray-400">.ik-minimize-</span>
            </div>
            

                  <div class="flex flex-col items-center justify-center gap-2 rounded-lg border border-gray-100 p-4 text-center transition hover:bg-gray-50">
                <i class="ik ik-minimize text-3xl text-primary-500"></i>
                <span class="text-sm font-medium text-gray-700">minimize</span>
                <span class="text-xs text-gray-400">.ik-minimize</span>
            </div>
            

                  <div class="flex flex-col items-center justify-center gap-2 rounded-lg border border-gray-100 p-4 text-center transition hover:bg-gray-50">
                <i class="ik ik-mic-off text-3xl text-primary-500"></i>
                <span class="text-sm font-medium text-gray-700">mic-off</span>
                <span class="text-xs text-gray-400">.ik-mic-off</span>
            </div>
            

                  <div class="flex flex-col items-center justify-center gap-2 rounded-lg border border-gray-100 p-4 text-center transition hover:bg-gray-50">
                <i class="ik ik-minus-circle text-3xl text-primary-500"></i>
                <span class="text-sm font-medium text-gray-700">minus-circle</span>
                <span class="text-xs text-gray-400">.ik-minus-circle</span>
            </div>
            

                  <div class="flex flex-col items-center justify-center gap-2 rounded-lg border border-gray-100 p-4 text-center transition hover:bg-gray-50">
                <i class="ik ik-mic text-3xl text-primary-500"></i>
                <span class="text-sm font-medium text-gray-700">mic</span>
                <span class="text-xs text-gray-400">.ik-mic</span>
            </div>
            

                  <div class="flex flex-col items-center justify-center gap-2 rounded-lg border border-gray-100 p-4 text-center transition hover:bg-gray-50">
                <i class="ik ik-minus-square text-3xl text-primary-500"></i>
                <span class="text-sm font-medium text-gray-700">minus-square</span>
                <span class="text-xs text-gray-400">.ik-minus-square</span>
            </div>
            

                  <div class="flex flex-col items-center justify-center gap-2 rounded-lg border border-gray-100 p-4 text-center transition hover:bg-gray-50">
                <i class="ik ik-minus text-3xl text-primary-500"></i>
                <span class="text-sm font-medium text-gray-700">minus</span>
                <span class="text-xs text-gray-400">.ik-minus</span>
            </div>
            

                  <div class="flex flex-col items-center justify-center gap-2 rounded-lg border border-gray-100 p-4 text-center transition hover:bg-gray-50">
                <i class="ik ik-moon text-3xl text-primary-500"></i>
                <span class="text-sm font-medium text-gray-700">moon</span>
                <span class="text-xs text-gray-400">.ik-moon</span>
            </div>
            

                  <div class="flex flex-col items-center justify-center gap-2 rounded-lg border border-gray-100 p-4 text-center transition hover:bg-gray-50">
                <i class="ik ik-monitor text-3xl text-primary-500"></i>
                <span class="text-sm font-medium text-gray-700">monitor</span>
                <span class="text-xs text-gray-400">.ik-monitor</span>
            </div>
            

                  <div class="flex flex-col items-center justify-center gap-2 rounded-lg border border-gray-100 p-4 text-center transition hover:bg-gray-50">
                <i class="ik ik-more-vertical text-3xl text-primary-500"></i>
                <span class="text-sm font-medium text-gray-700">more-vertical</span>
                <span class="text-xs text-gray-400">.ik-more-vertical</span>
            </div>
            

                  <div class="flex flex-col items-center justify-center gap-2 rounded-lg border border-gray-100 p-4 text-center transition hover:bg-gray-50">
                <i class="ik ik-more-horizontal text-3xl text-primary-500"></i>
                <span class="text-sm font-medium text-gray-700">more-horizontal</span>
                <span class="text-xs text-gray-400">.ik-more-horizontal</span>
            </div>
            

                  <div class="flex flex-col items-center justify-center gap-2 rounded-lg border border-gray-100 p-4 text-center transition hover:bg-gray-50">
                <i class="ik ik-move text-3xl text-primary-500"></i>
                <span class="text-sm font-medium text-gray-700">move</span>
                <span class="text-xs text-gray-400">.ik-move</span>
            </div>
            

                  <div class="flex flex-col items-center justify-center gap-2 rounded-lg border border-gray-100 p-4 text-center transition hover:bg-gray-50">
                <i class="ik ik-music text-3xl text-primary-500"></i>
                <span class="text-sm font-medium text-gray-700">music</span>
                <span class="text-xs text-gray-400">.ik-music</span>
            </div>
            

                  <div class="flex flex-col items-center justify-center gap-2 rounded-lg border border-gray-100 p-4 text-center transition hover:bg-gray-50">
                <i class="ik ik-navigation-2 text-3xl text-primary-500"></i>
                <span class="text-sm font-medium text-gray-700">navigation-2</span>
                <span class="text-xs text-gray-400">.ik-navigation-</span>
            </div>
            

                  <div class="flex flex-col items-center justify-center gap-2 rounded-lg border border-gray-100 p-4 text-center transition hover:bg-gray-50">
                <i class="ik ik-navigation text-3xl text-primary-500"></i>
                <span class="text-sm font-medium text-gray-700">navigation</span>
                <span class="text-xs text-gray-400">.ik-navigation</span>
            </div>
            

                  <div class="flex flex-col items-center justify-center gap-2 rounded-lg border border-gray-100 p-4 text-center transition hover:bg-gray-50">
                <i class="ik ik-octagon text-3xl text-primary-500"></i>
                <span class="text-sm font-medium text-gray-700">octagon</span>
                <span class="text-xs text-gray-400">.ik-octagon</span>
            </div>
            

                  <div class="flex flex-col items-center justify-center gap-2 rounded-lg border border-gray-100 p-4 text-center transition hover:bg-gray-50">
                <i class="ik ik-package text-3xl text-primary-500"></i>
                <span class="text-sm font-medium text-gray-700">package</span>
                <span class="text-xs text-gray-400">.ik-package</span>
            </div>
            

                  <div class="flex flex-col items-center justify-center gap-2 rounded-lg border border-gray-100 p-4 text-center transition hover:bg-gray-50">
                <i class="ik ik-pause-circle text-3xl text-primary-500"></i>
                <span class="text-sm font-medium text-gray-700">pause-circle</span>
                <span class="text-xs text-gray-400">.ik-pause-circle</span>
            </div>
            

                  <div class="flex flex-col items-center justify-center gap-2 rounded-lg border border-gray-100 p-4 text-center transition hover:bg-gray-50">
                <i class="ik ik-pause text-3xl text-primary-500"></i>
                <span class="text-sm font-medium text-gray-700">pause</span>
                <span class="text-xs text-gray-400">.ik-pause</span>
            </div>
            

                  <div class="flex flex-col items-center justify-center gap-2 rounded-lg border border-gray-100 p-4 text-center transition hover:bg-gray-50">
                <i class="ik ik-percent text-3xl text-primary-500"></i>
                <span class="text-sm font-medium text-gray-700">percent</span>
                <span class="text-xs text-gray-400">.ik-percent</span>
            </div>
            

                  <div class="flex flex-col items-center justify-center gap-2 rounded-lg border border-gray-100 p-4 text-center transition hover:bg-gray-50">
                <i class="ik ik-phone-call text-3xl text-primary-500"></i>
                <span class="text-sm font-medium text-gray-700">phone-call</span>
                <span class="text-xs text-gray-400">.ik-phone-call</span>
            </div>
            

                  <div class="flex flex-col items-center justify-center gap-2 rounded-lg border border-gray-100 p-4 text-center transition hover:bg-gray-50">
                <i class="ik ik-phone-forwarded text-3xl text-primary-500"></i>
                <span class="text-sm font-medium text-gray-700">phone-forwarded</span>
                <span class="text-xs text-gray-400">.ik-phone-forwarded</span>
            </div>
            

                  <div class="flex flex-col items-center justify-center gap-2 rounded-lg border border-gray-100 p-4 text-center transition hover:bg-gray-50">
                <i class="ik ik-phone-missed text-3xl text-primary-500"></i>
                <span class="text-sm font-medium text-gray-700">phone-missed</span>
                <span class="text-xs text-gray-400">.ik-phone-missed</span>
            </div>
            

                  <div class="flex flex-col items-center justify-center gap-2 rounded-lg border border-gray-100 p-4 text-center transition hover:bg-gray-50">
                <i class="ik ik-phone-off text-3xl text-primary-500"></i>
                <span class="text-sm font-medium text-gray-700">phone-off</span>
                <span class="text-xs text-gray-400">.ik-phone-off</span>
            </div>
            

                  <div class="flex flex-col items-center justify-center gap-2 rounded-lg border border-gray-100 p-4 text-center transition hover:bg-gray-50">
                <i class="ik ik-phone-incoming text-3xl text-primary-500"></i>
                <span class="text-sm font-medium text-gray-700">phone-incoming</span>
                <span class="text-xs text-gray-400">.ik-phone-incoming</span>
            </div>
            

                  <div class="flex flex-col items-center justify-center gap-2 rounded-lg border border-gray-100 p-4 text-center transition hover:bg-gray-50">
                <i class="ik ik-phone text-3xl text-primary-500"></i>
                <span class="text-sm font-medium text-gray-700">phone</span>
                <span class="text-xs text-gray-400">.ik-phone</span>
            </div>
            

                  <div class="flex flex-col items-center justify-center gap-2 rounded-lg border border-gray-100 p-4 text-center transition hover:bg-gray-50">
                <i class="ik ik-phone-outgoing text-3xl text-primary-500"></i>
                <span class="text-sm font-medium text-gray-700">phone-outgoing</span>
                <span class="text-xs text-gray-400">.ik-phone-outgoing</span>
            </div>
            

                  <div class="flex flex-col items-center justify-center gap-2 rounded-lg border border-gray-100 p-4 text-center transition hover:bg-gray-50">
                <i class="ik ik-pie-chart text-3xl text-primary-500"></i>
                <span class="text-sm font-medium text-gray-700">pie-chart</span>
                <span class="text-xs text-gray-400">.ik-pie-chart</span>
            </div>
            

                  <div class="flex flex-col items-center justify-center gap-2 rounded-lg border border-gray-100 p-4 text-center transition hover:bg-gray-50">
                <i class="ik ik-play-circle text-3xl text-primary-500"></i>
                <span class="text-sm font-medium text-gray-700">play-circle</span>
                <span class="text-xs text-gray-400">.ik-play-circle</span>
            </div>
            

                  <div class="flex flex-col items-center justify-center gap-2 rounded-lg border border-gray-100 p-4 text-center transition hover:bg-gray-50">
                <i class="ik ik-play text-3xl text-primary-500"></i>
                <span class="text-sm font-medium text-gray-700">play</span>
                <span class="text-xs text-gray-400">.ik-play</span>
            </div>
            

                  <div class="flex flex-col items-center justify-center gap-2 rounded-lg border border-gray-100 p-4 text-center transition hover:bg-gray-50">
                <i class="ik ik-plus-square text-3xl text-primary-500"></i>
                <span class="text-sm font-medium text-gray-700">plus-square</span>
                <span class="text-xs text-gray-400">.ik-plus-square</span>
            </div>
            

                  <div class="flex flex-col items-center justify-center gap-2 rounded-lg border border-gray-100 p-4 text-center transition hover:bg-gray-50">
                <i class="ik ik-plus-circle text-3xl text-primary-500"></i>
                <span class="text-sm font-medium text-gray-700">plus-circle</span>
                <span class="text-xs text-gray-400">.ik-plus-circle</span>
            </div>
            

                  <div class="flex flex-col items-center justify-center gap-2 rounded-lg border border-gray-100 p-4 text-center transition hover:bg-gray-50">
                <i class="ik ik-plus text-3xl text-primary-500"></i>
                <span class="text-sm font-medium text-gray-700">plus</span>
                <span class="text-xs text-gray-400">.ik-plus</span>
            </div>
            

                  <div class="flex flex-col items-center justify-center gap-2 rounded-lg border border-gray-100 p-4 text-center transition hover:bg-gray-50">
                <i class="ik ik-pocket text-3xl text-primary-500"></i>
                <span class="text-sm font-medium text-gray-700">pocket</span>
                <span class="text-xs text-gray-400">.ik-pocket</span>
            </div>
            

                  <div class="flex flex-col items-center justify-center gap-2 rounded-lg border border-gray-100 p-4 text-center transition hover:bg-gray-50">
                <i class="ik ik-printer text-3xl text-primary-500"></i>
                <span class="text-sm font-medium text-gray-700">printer</span>
                <span class="text-xs text-gray-400">.ik-printer</span>
            </div>
            

                  <div class="flex flex-col items-center justify-center gap-2 rounded-lg border border-gray-100 p-4 text-center transition hover:bg-gray-50">
                <i class="ik ik-power text-3xl text-primary-500"></i>
                <span class="text-sm font-medium text-gray-700">power</span>
                <span class="text-xs text-gray-400">.ik-power</span>
            </div>
            

                  <div class="flex flex-col items-center justify-center gap-2 rounded-lg border border-gray-100 p-4 text-center transition hover:bg-gray-50">
                <i class="ik ik-radio text-3xl text-primary-500"></i>
                <span class="text-sm font-medium text-gray-700">radio</span>
                <span class="text-xs text-gray-400">.ik-radio</span>
            </div>
            

                  <div class="flex flex-col items-center justify-center gap-2 rounded-lg border border-gray-100 p-4 text-center transition hover:bg-gray-50">
                <i class="ik ik-repeat text-3xl text-primary-500"></i>
                <span class="text-sm font-medium text-gray-700">repeat</span>
                <span class="text-xs text-gray-400">.ik-repeat</span>
            </div>
            

                  <div class="flex flex-col items-center justify-center gap-2 rounded-lg border border-gray-100 p-4 text-center transition hover:bg-gray-50">
                <i class="ik ik-refresh-ccw text-3xl text-primary-500"></i>
                <span class="text-sm font-medium text-gray-700">refresh-ccw</span>
                <span class="text-xs text-gray-400">.ik-refresh-ccw</span>
            </div>
            

                  <div class="flex flex-col items-center justify-center gap-2 rounded-lg border border-gray-100 p-4 text-center transition hover:bg-gray-50">
                <i class="ik ik-rewind text-3xl text-primary-500"></i>
                <span class="text-sm font-medium text-gray-700">rewind</span>
                <span class="text-xs text-gray-400">.ik-rewind</span>
            </div>
            

                  <div class="flex flex-col items-center justify-center gap-2 rounded-lg border border-gray-100 p-4 text-center transition hover:bg-gray-50">
                <i class="ik ik-rotate-ccw text-3xl text-primary-500"></i>
                <span class="text-sm font-medium text-gray-700">rotate-ccw</span>
                <span class="text-xs text-gray-400">.ik-rotate-ccw</span>
            </div>
            

                  <div class="flex flex-col items-center justify-center gap-2 rounded-lg border border-gray-100 p-4 text-center transition hover:bg-gray-50">
                <i class="ik ik-refresh-cw text-3xl text-primary-500"></i>
                <span class="text-sm font-medium text-gray-700">refresh-cw</span>
                <span class="text-xs text-gray-400">.ik-refresh-cw</span>
            </div>
            

                  <div class="flex flex-col items-center justify-center gap-2 rounded-lg border border-gray-100 p-4 text-center transition hover:bg-gray-50">
                <i class="ik ik-rotate-cw text-3xl text-primary-500"></i>
                <span class="text-sm font-medium text-gray-700">rotate-cw</span>
                <span class="text-xs text-gray-400">.ik-rotate-cw</span>
            </div>
            

                  <div class="flex flex-col items-center justify-center gap-2 rounded-lg border border-gray-100 p-4 text-center transition hover:bg-gray-50">
                <i class="ik ik-save text-3xl text-primary-500"></i>
                <span class="text-sm font-medium text-gray-700">save</span>
                <span class="text-xs text-gray-400">.ik-save</span>
            </div>
            

                  <div class="flex flex-col items-center justify-center gap-2 rounded-lg border border-gray-100 p-4 text-center transition hover:bg-gray-50">
                <i class="ik ik-search text-3xl text-primary-500"></i>
                <span class="text-sm font-medium text-gray-700">search</span>
                <span class="text-xs text-gray-400">.ik-search</span>
            </div>
            

                  <div class="flex flex-col items-center justify-center gap-2 rounded-lg border border-gray-100 p-4 text-center transition hover:bg-gray-50">
                <i class="ik ik-server text-3xl text-primary-500"></i>
                <span class="text-sm font-medium text-gray-700">server</span>
                <span class="text-xs text-gray-400">.ik-server</span>
            </div>
            

                  <div class="flex flex-col items-center justify-center gap-2 rounded-lg border border-gray-100 p-4 text-center transition hover:bg-gray-50">
                <i class="ik ik-scissors text-3xl text-primary-500"></i>
                <span class="text-sm font-medium text-gray-700">scissors</span>
                <span class="text-xs text-gray-400">.ik-scissors</span>
            </div>
            

                  <div class="flex flex-col items-center justify-center gap-2 rounded-lg border border-gray-100 p-4 text-center transition hover:bg-gray-50">
                <i class="ik ik-share-2 text-3xl text-primary-500"></i>
                <span class="text-sm font-medium text-gray-700">share-2</span>
                <span class="text-xs text-gray-400">.ik-share-</span>
            </div>
            

                  <div class="flex flex-col items-center justify-center gap-2 rounded-lg border border-gray-100 p-4 text-center transition hover:bg-gray-50">
                <i class="ik ik-share text-3xl text-primary-500"></i>
                <span class="text-sm font-medium text-gray-700">share</span>
                <span class="text-xs text-gray-400">.ik-share</span>
            </div>
            

                  <div class="flex flex-col items-center justify-center gap-2 rounded-lg border border-gray-100 p-4 text-center transition hover:bg-gray-50">
                <i class="ik ik-shield text-3xl text-primary-500"></i>
                <span class="text-sm font-medium text-gray-700">shield</span>
                <span class="text-xs text-gray-400">.ik-shield</span>
            </div>
            

                  <div class="flex flex-col items-center justify-center gap-2 rounded-lg border border-gray-100 p-4 text-center transition hover:bg-gray-50">
                <i class="ik ik-settings text-3xl text-primary-500"></i>
                <span class="text-sm font-medium text-gray-700">settings</span>
                <span class="text-xs text-gray-400">.ik-settings</span>
            </div>
            

                  <div class="flex flex-col items-center justify-center gap-2 rounded-lg border border-gray-100 p-4 text-center transition hover:bg-gray-50">
                <i class="ik ik-skip-back text-3xl text-primary-500"></i>
                <span class="text-sm font-medium text-gray-700">skip-back</span>
                <span class="text-xs text-gray-400">.ik-skip-back</span>
            </div>
            

                  <div class="flex flex-col items-center justify-center gap-2 rounded-lg border border-gray-100 p-4 text-center transition hover:bg-gray-50">
                <i class="ik ik-shuffle text-3xl text-primary-500"></i>
                <span class="text-sm font-medium text-gray-700">shuffle</span>
                <span class="text-xs text-gray-400">.ik-shuffle</span>
            </div>
            

                  <div class="flex flex-col items-center justify-center gap-2 rounded-lg border border-gray-100 p-4 text-center transition hover:bg-gray-50">
                <i class="ik ik-sidebar text-3xl text-primary-500"></i>
                <span class="text-sm font-medium text-gray-700">sidebar</span>
                <span class="text-xs text-gray-400">.ik-sidebar</span>
            </div>
            

                  <div class="flex flex-col items-center justify-center gap-2 rounded-lg border border-gray-100 p-4 text-center transition hover:bg-gray-50">
                <i class="ik ik-skip-forward text-3xl text-primary-500"></i>
                <span class="text-sm font-medium text-gray-700">skip-forward</span>
                <span class="text-xs text-gray-400">.ik-skip-forward</span>
            </div>
            

                  <div class="flex flex-col items-center justify-center gap-2 rounded-lg border border-gray-100 p-4 text-center transition hover:bg-gray-50">
                <i class="ik ik-slack text-3xl text-primary-500"></i>
                <span class="text-sm font-medium text-gray-700">slack</span>
                <span class="text-xs text-gray-400">.ik-slack</span>
            </div>
            

                  <div class="flex flex-col items-center justify-center gap-2 rounded-lg border border-gray-100 p-4 text-center transition hover:bg-gray-50">
                <i class="ik ik-slash text-3xl text-primary-500"></i>
                <span class="text-sm font-medium text-gray-700">slash</span>
                <span class="text-xs text-gray-400">.ik-slash</span>
            </div>
            

                  <div class="flex flex-col items-center justify-center gap-2 rounded-lg border border-gray-100 p-4 text-center transition hover:bg-gray-50">
                <i class="ik ik-smartphone text-3xl text-primary-500"></i>
                <span class="text-sm font-medium text-gray-700">smartphone</span>
                <span class="text-xs text-gray-400">.ik-smartphone</span>
            </div>
            

                  <div class="flex flex-col items-center justify-center gap-2 rounded-lg border border-gray-100 p-4 text-center transition hover:bg-gray-50">
                <i class="ik ik-square text-3xl text-primary-500"></i>
                <span class="text-sm font-medium text-gray-700">square</span>
                <span class="text-xs text-gray-400">.ik-square</span>
            </div>
            

                  <div class="flex flex-col items-center justify-center gap-2 rounded-lg border border-gray-100 p-4 text-center transition hover:bg-gray-50">
                <i class="ik ik-speaker text-3xl text-primary-500"></i>
                <span class="text-sm font-medium text-gray-700">speaker</span>
                <span class="text-xs text-gray-400">.ik-speaker</span>
            </div>
            

                  <div class="flex flex-col items-center justify-center gap-2 rounded-lg border border-gray-100 p-4 text-center transition hover:bg-gray-50">
                <i class="ik ik-star text-3xl text-primary-500"></i>
                <span class="text-sm font-medium text-gray-700">star</span>
                <span class="text-xs text-gray-400">.ik-star</span>
            </div>
            

                  <div class="flex flex-col items-center justify-center gap-2 rounded-lg border border-gray-100 p-4 text-center transition hover:bg-gray-50">
                <i class="ik ik-stop-circle text-3xl text-primary-500"></i>
                <span class="text-sm font-medium text-gray-700">stop-circle</span>
                <span class="text-xs text-gray-400">.ik-stop-circle</span>
            </div>
            

                  <div class="flex flex-col items-center justify-center gap-2 rounded-lg border border-gray-100 p-4 text-center transition hover:bg-gray-50">
                <i class="ik ik-sun text-3xl text-primary-500"></i>
                <span class="text-sm font-medium text-gray-700">sun</span>
                <span class="text-xs text-gray-400">.ik-sun</span>
            </div>
            

                  <div class="flex flex-col items-center justify-center gap-2 rounded-lg border border-gray-100 p-4 text-center transition hover:bg-gray-50">
                <i class="ik ik-sunrise text-3xl text-primary-500"></i>
                <span class="text-sm font-medium text-gray-700">sunrise</span>
                <span class="text-xs text-gray-400">.ik-sunrise</span>
            </div>
            

                  <div class="flex flex-col items-center justify-center gap-2 rounded-lg border border-gray-100 p-4 text-center transition hover:bg-gray-50">
                <i class="ik ik-tablet text-3xl text-primary-500"></i>
                <span class="text-sm font-medium text-gray-700">tablet</span>
                <span class="text-xs text-gray-400">.ik-tablet</span>
            </div>
            

                  <div class="flex flex-col items-center justify-center gap-2 rounded-lg border border-gray-100 p-4 text-center transition hover:bg-gray-50">
                <i class="ik ik-tag text-3xl text-primary-500"></i>
                <span class="text-sm font-medium text-gray-700">tag</span>
                <span class="text-xs text-gray-400">.ik-tag</span>
            </div>
            

                  <div class="flex flex-col items-center justify-center gap-2 rounded-lg border border-gray-100 p-4 text-center transition hover:bg-gray-50">
                <i class="ik ik-sunset text-3xl text-primary-500"></i>
                <span class="text-sm font-medium text-gray-700">sunset</span>
                <span class="text-xs text-gray-400">.ik-sunset</span>
            </div>
            

                  <div class="flex flex-col items-center justify-center gap-2 rounded-lg border border-gray-100 p-4 text-center transition hover:bg-gray-50">
                <i class="ik ik-target text-3xl text-primary-500"></i>
                <span class="text-sm font-medium text-gray-700">target</span>
                <span class="text-xs text-gray-400">.ik-target</span>
            </div>
            

                  <div class="flex flex-col items-center justify-center gap-2 rounded-lg border border-gray-100 p-4 text-center transition hover:bg-gray-50">
                <i class="ik ik-thermometer text-3xl text-primary-500"></i>
                <span class="text-sm font-medium text-gray-700">thermometer</span>
                <span class="text-xs text-gray-400">.ik-thermometer</span>
            </div>
            

                  <div class="flex flex-col items-center justify-center gap-2 rounded-lg border border-gray-100 p-4 text-center transition hover:bg-gray-50">
                <i class="ik ik-thumbs-up text-3xl text-primary-500"></i>
                <span class="text-sm font-medium text-gray-700">thumbs-up</span>
                <span class="text-xs text-gray-400">.ik-thumbs-up</span>
            </div>
            

                  <div class="flex flex-col items-center justify-center gap-2 rounded-lg border border-gray-100 p-4 text-center transition hover:bg-gray-50">
                <i class="ik ik-thumbs-down text-3xl text-primary-500"></i>
                <span class="text-sm font-medium text-gray-700">thumbs-down</span>
                <span class="text-xs text-gray-400">.ik-thumbs-down</span>
            </div>
            

                  <div class="flex flex-col items-center justify-center gap-2 rounded-lg border border-gray-100 p-4 text-center transition hover:bg-gray-50">
                <i class="ik ik-toggle-left text-3xl text-primary-500"></i>
                <span class="text-sm font-medium text-gray-700">toggle-left</span>
                <span class="text-xs text-gray-400">.ik-toggle-left</span>
            </div>
            

                  <div class="flex flex-col items-center justify-center gap-2 rounded-lg border border-gray-100 p-4 text-center transition hover:bg-gray-50">
                <i class="ik ik-toggle-right text-3xl text-primary-500"></i>
                <span class="text-sm font-medium text-gray-700">toggle-right</span>
                <span class="text-xs text-gray-400">.ik-toggle-right</span>
            </div>
            

                  <div class="flex flex-col items-center justify-center gap-2 rounded-lg border border-gray-100 p-4 text-center transition hover:bg-gray-50">
                <i class="ik ik-trash-2 text-3xl text-primary-500"></i>
                <span class="text-sm font-medium text-gray-700">trash-2</span>
                <span class="text-xs text-gray-400">.ik-trash-</span>
            </div>
            

                  <div class="flex flex-col items-center justify-center gap-2 rounded-lg border border-gray-100 p-4 text-center transition hover:bg-gray-50">
                <i class="ik ik-trash text-3xl text-primary-500"></i>
                <span class="text-sm font-medium text-gray-700">trash</span>
                <span class="text-xs text-gray-400">.ik-trash</span>
            </div>
            

                  <div class="flex flex-col items-center justify-center gap-2 rounded-lg border border-gray-100 p-4 text-center transition hover:bg-gray-50">
                <i class="ik ik-trending-up text-3xl text-primary-500"></i>
                <span class="text-sm font-medium text-gray-700">trending-up</span>
                <span class="text-xs text-gray-400">.ik-trending-up</span>
            </div>
            

                  <div class="flex flex-col items-center justify-center gap-2 rounded-lg border border-gray-100 p-4 text-center transition hover:bg-gray-50">
                <i class="ik ik-trending-down text-3xl text-primary-500"></i>
                <span class="text-sm font-medium text-gray-700">trending-down</span>
                <span class="text-xs text-gray-400">.ik-trending-down</span>
            </div>
            

                  <div class="flex flex-col items-center justify-center gap-2 rounded-lg border border-gray-100 p-4 text-center transition hover:bg-gray-50">
                <i class="ik ik-triangle text-3xl text-primary-500"></i>
                <span class="text-sm font-medium text-gray-700">triangle</span>
                <span class="text-xs text-gray-400">.ik-triangle</span>
            </div>
            

                  <div class="flex flex-col items-center justify-center gap-2 rounded-lg border border-gray-100 p-4 text-center transition hover:bg-gray-50">
                <i class="ik ik-type text-3xl text-primary-500"></i>
                <span class="text-sm font-medium text-gray-700">type</span>
                <span class="text-xs text-gray-400">.ik-type</span>
            </div>
            

                  <div class="flex flex-col items-center justify-center gap-2 rounded-lg border border-gray-100 p-4 text-center transition hover:bg-gray-50">
                <i class="ik ik-twitter text-3xl text-primary-500"></i>
                <span class="text-sm font-medium text-gray-700">twitter</span>
                <span class="text-xs text-gray-400">.ik-twitter</span>
            </div>
            

                  <div class="flex flex-col items-center justify-center gap-2 rounded-lg border border-gray-100 p-4 text-center transition hover:bg-gray-50">
                <i class="ik ik-upload text-3xl text-primary-500"></i>
                <span class="text-sm font-medium text-gray-700">upload</span>
                <span class="text-xs text-gray-400">.ik-upload</span>
            </div>
            

                  <div class="flex flex-col items-center justify-center gap-2 rounded-lg border border-gray-100 p-4 text-center transition hover:bg-gray-50">
                <i class="ik ik-umbrella text-3xl text-primary-500"></i>
                <span class="text-sm font-medium text-gray-700">umbrella</span>
                <span class="text-xs text-gray-400">.ik-umbrella</span>
            </div>
            

                  <div class="flex flex-col items-center justify-center gap-2 rounded-lg border border-gray-100 p-4 text-center transition hover:bg-gray-50">
                <i class="ik ik-upload-cloud text-3xl text-primary-500"></i>
                <span class="text-sm font-medium text-gray-700">upload-cloud</span>
                <span class="text-xs text-gray-400">.ik-upload-cloud</span>
            </div>
            

                  <div class="flex flex-col items-center justify-center gap-2 rounded-lg border border-gray-100 p-4 text-center transition hover:bg-gray-50">
                <i class="ik ik-unlock text-3xl text-primary-500"></i>
                <span class="text-sm font-medium text-gray-700">unlock</span>
                <span class="text-xs text-gray-400">.ik-unlock</span>
            </div>
            

                  <div class="flex flex-col items-center justify-center gap-2 rounded-lg border border-gray-100 p-4 text-center transition hover:bg-gray-50">
                <i class="ik ik-user-check text-3xl text-primary-500"></i>
                <span class="text-sm font-medium text-gray-700">user-check</span>
                <span class="text-xs text-gray-400">.ik-user-check</span>
            </div>
            

                  <div class="flex flex-col items-center justify-center gap-2 rounded-lg border border-gray-100 p-4 text-center transition hover:bg-gray-50">
                <i class="ik ik-user-minus text-3xl text-primary-500"></i>
                <span class="text-sm font-medium text-gray-700">user-minus</span>
                <span class="text-xs text-gray-400">.ik-user-minus</span>
            </div>
            

                  <div class="flex flex-col items-center justify-center gap-2 rounded-lg border border-gray-100 p-4 text-center transition hover:bg-gray-50">
                <i class="ik ik-user-plus text-3xl text-primary-500"></i>
                <span class="text-sm font-medium text-gray-700">user-plus</span>
                <span class="text-xs text-gray-400">.ik-user-plus</span>
            </div>
            

                  <div class="flex flex-col items-center justify-center gap-2 rounded-lg border border-gray-100 p-4 text-center transition hover:bg-gray-50">
                <i class="ik ik-user-x text-3xl text-primary-500"></i>
                <span class="text-sm font-medium text-gray-700">user-x</span>
                <span class="text-xs text-gray-400">.ik-user-x</span>
            </div>
            

                  <div class="flex flex-col items-center justify-center gap-2 rounded-lg border border-gray-100 p-4 text-center transition hover:bg-gray-50">
                <i class="ik ik-user text-3xl text-primary-500"></i>
                <span class="text-sm font-medium text-gray-700">user</span>
                <span class="text-xs text-gray-400">.ik-user</span>
            </div>
            

                  <div class="flex flex-col items-center justify-center gap-2 rounded-lg border border-gray-100 p-4 text-center transition hover:bg-gray-50">
                <i class="ik ik-users text-3xl text-primary-500"></i>
                <span class="text-sm font-medium text-gray-700">users</span>
                <span class="text-xs text-gray-400">.ik-users</span>
            </div>
            

                  <div class="flex flex-col items-center justify-center gap-2 rounded-lg border border-gray-100 p-4 text-center transition hover:bg-gray-50">
                <i class="ik ik-video-off text-3xl text-primary-500"></i>
                <span class="text-sm font-medium text-gray-700">video-off</span>
                <span class="text-xs text-gray-400">.ik-video-off</span>
            </div>
            

                  <div class="flex flex-col items-center justify-center gap-2 rounded-lg border border-gray-100 p-4 text-center transition hover:bg-gray-50">
                <i class="ik ik-video text-3xl text-primary-500"></i>
                <span class="text-sm font-medium text-gray-700">video</span>
                <span class="text-xs text-gray-400">.ik-video</span>
            </div>
            

                  <div class="flex flex-col items-center justify-center gap-2 rounded-lg border border-gray-100 p-4 text-center transition hover:bg-gray-50">
                <i class="ik ik-voicemail text-3xl text-primary-500"></i>
                <span class="text-sm font-medium text-gray-700">voicemail</span>
                <span class="text-xs text-gray-400">.ik-voicemail</span>
            </div>
            

                  <div class="flex flex-col items-center justify-center gap-2 rounded-lg border border-gray-100 p-4 text-center transition hover:bg-gray-50">
                <i class="ik ik-volume-x text-3xl text-primary-500"></i>
                <span class="text-sm font-medium text-gray-700">volume-x</span>
                <span class="text-xs text-gray-400">.ik-volume-x</span>
            </div>
            

                  <div class="flex flex-col items-center justify-center gap-2 rounded-lg border border-gray-100 p-4 text-center transition hover:bg-gray-50">
                <i class="ik ik-volume-2 text-3xl text-primary-500"></i>
                <span class="text-sm font-medium text-gray-700">volume-1</span>
                <span class="text-xs text-gray-400">.ik-volume-</span>
            </div>
            

                  <div class="flex flex-col items-center justify-center gap-2 rounded-lg border border-gray-100 p-4 text-center transition hover:bg-gray-50">
                <i class="ik ik-volume-1 text-3xl text-primary-500"></i>
                <span class="text-sm font-medium text-gray-700">volume-2</span>
                <span class="text-xs text-gray-400">.ik-volume-1</span>
            </div>
            

                  <div class="flex flex-col items-center justify-center gap-2 rounded-lg border border-gray-100 p-4 text-center transition hover:bg-gray-50">
                <i class="ik ik-volume text-3xl text-primary-500"></i>
                <span class="text-sm font-medium text-gray-700">volume</span>
                <span class="text-xs text-gray-400">.ik-volume</span>
            </div>
            

                  <div class="flex flex-col items-center justify-center gap-2 rounded-lg border border-gray-100 p-4 text-center transition hover:bg-gray-50">
                <i class="ik ik-watch text-3xl text-primary-500"></i>
                <span class="text-sm font-medium text-gray-700">watch</span>
                <span class="text-xs text-gray-400">.ik-watch</span>
            </div>
            

                  <div class="flex flex-col items-center justify-center gap-2 rounded-lg border border-gray-100 p-4 text-center transition hover:bg-gray-50">
                <i class="ik ik-wifi text-3xl text-primary-500"></i>
                <span class="text-sm font-medium text-gray-700">wifi</span>
                <span class="text-xs text-gray-400">.ik-wifi</span>
            </div>
            

                  <div class="flex flex-col items-center justify-center gap-2 rounded-lg border border-gray-100 p-4 text-center transition hover:bg-gray-50">
                <i class="ik ik-x-square text-3xl text-primary-500"></i>
                <span class="text-sm font-medium text-gray-700">x-square</span>
                <span class="text-xs text-gray-400">.ik-x-square</span>
            </div>
            

                  <div class="flex flex-col items-center justify-center gap-2 rounded-lg border border-gray-100 p-4 text-center transition hover:bg-gray-50">
                <i class="ik ik-wind text-3xl text-primary-500"></i>
                <span class="text-sm font-medium text-gray-700">wind</span>
                <span class="text-xs text-gray-400">.ik-wind</span>
            </div>
            

                  <div class="flex flex-col items-center justify-center gap-2 rounded-lg border border-gray-100 p-4 text-center transition hover:bg-gray-50">
                <i class="ik ik-x text-3xl text-primary-500"></i>
                <span class="text-sm font-medium text-gray-700">x</span>
                <span class="text-xs text-gray-400">.ik-x</span>
            </div>
            

                  <div class="flex flex-col items-center justify-center gap-2 rounded-lg border border-gray-100 p-4 text-center transition hover:bg-gray-50">
                <i class="ik ik-x-circle text-3xl text-primary-500"></i>
                <span class="text-sm font-medium text-gray-700">x-circle</span>
                <span class="text-xs text-gray-400">.ik-x-circle</span>
            </div>
            

                  <div class="flex flex-col items-center justify-center gap-2 rounded-lg border border-gray-100 p-4 text-center transition hover:bg-gray-50">
                <i class="ik ik-zap text-3xl text-primary-500"></i>
                <span class="text-sm font-medium text-gray-700">zap</span>
                <span class="text-xs text-gray-400">.ik-zap</span>
            </div>
            

                  <div class="flex flex-col items-center justify-center gap-2 rounded-lg border border-gray-100 p-4 text-center transition hover:bg-gray-50">
                <i class="ik ik-zoom-in text-3xl text-primary-500"></i>
                <span class="text-sm font-medium text-gray-700">zoom-in</span>
                <span class="text-xs text-gray-400">.ik-zoom-in</span>
            </div>
            

                  <div class="flex flex-col items-center justify-center gap-2 rounded-lg border border-gray-100 p-4 text-center transition hover:bg-gray-50">
                <i class="ik ik-zoom-out text-3xl text-primary-500"></i>
                <span class="text-sm font-medium text-gray-700">zoom-out</span>
                <span class="text-xs text-gray-400">.ik-zoom-out</span>
            </div>
            

                  <div class="flex flex-col items-center justify-center gap-2 rounded-lg border border-gray-100 p-4 text-center transition hover:bg-gray-50">
                <i class="ik ik-command text-3xl text-primary-500"></i>
                <span class="text-sm font-medium text-gray-700">command</span>
                <span class="text-xs text-gray-400">.ik-command</span>
            </div>
            

                  <div class="flex flex-col items-center justify-center gap-2 rounded-lg border border-gray-100 p-4 text-center transition hover:bg-gray-50">
                <i class="ik ik-cloud text-3xl text-primary-500"></i>
                <span class="text-sm font-medium text-gray-700">cloud</span>
                <span class="text-xs text-gray-400">.ik-cloud</span>
            </div>
            

                  <div class="flex flex-col items-center justify-center gap-2 rounded-lg border border-gray-100 p-4 text-center transition hover:bg-gray-50">
                <i class="ik ik-hash text-3xl text-primary-500"></i>
                <span class="text-sm font-medium text-gray-700">hash</span>
                <span class="text-xs text-gray-400">.ik-hash</span>
            </div>
            

                  <div class="flex flex-col items-center justify-center gap-2 rounded-lg border border-gray-100 p-4 text-center transition hover:bg-gray-50">
                <i class="ik ik-headphones text-3xl text-primary-500"></i>
                <span class="text-sm font-medium text-gray-700">headphones</span>
                <span class="text-xs text-gray-400">.ik-headphones</span>
            </div>
            

                  <div class="flex flex-col items-center justify-center gap-2 rounded-lg border border-gray-100 p-4 text-center transition hover:bg-gray-50">
                <i class="ik ik-underline text-3xl text-primary-500"></i>
                <span class="text-sm font-medium text-gray-700">underline</span>
                <span class="text-xs text-gray-400">.ik-underline</span>
            </div>
            
                  
                  <div class="flex flex-col items-center justify-center gap-2 rounded-lg border border-gray-100 p-4 text-center transition hover:bg-gray-50">
                <i class="ik ik-italic text-3xl text-primary-500"></i>
                <span class="text-sm font-medium text-gray-700">italic</span>
                <span class="text-xs text-gray-400">.ik-italic</span>
            </div>
            
                  
                  <div class="flex flex-col items-center justify-center gap-2 rounded-lg border border-gray-100 p-4 text-center transition hover:bg-gray-50">
                <i class="ik ik-bold text-3xl text-primary-500"></i>
                <span class="text-sm font-medium text-gray-700">bold</span>
                <span class="text-xs text-gray-400">.ik-bold</span>
            </div>
            

                  <div class="flex flex-col items-center justify-center gap-2 rounded-lg border border-gray-100 p-4 text-center transition hover:bg-gray-50">
                <i class="ik ik-crop text-3xl text-primary-500"></i>
                <span class="text-sm font-medium text-gray-700">crop</span>
                <span class="text-xs text-gray-400">.ik-crop</span>
            </div>
            
                  
                  <div class="flex flex-col items-center justify-center gap-2 rounded-lg border border-gray-100 p-4 text-center transition hover:bg-gray-50">
                <i class="ik ik-help-circle text-3xl text-primary-500"></i>
                <span class="text-sm font-medium text-gray-700">help-circle</span>
                <span class="text-xs text-gray-400">.ik-help-circle</span>
            </div>
            
                  
                  <div class="flex flex-col items-center justify-center gap-2 rounded-lg border border-gray-100 p-4 text-center transition hover:bg-gray-50">
                <i class="ik ik-paperclip text-3xl text-primary-500"></i>
                <span class="text-sm font-medium text-gray-700">paperclip</span>
                <span class="text-xs text-gray-400">.ik-paperclip</span>
            </div>
            
                  
                  <div class="flex flex-col items-center justify-center gap-2 rounded-lg border border-gray-100 p-4 text-center transition hover:bg-gray-50">
                <i class="ik ik-shopping-cart text-3xl text-primary-500"></i>
                <span class="text-sm font-medium text-gray-700">shopping-cart</span>
                <span class="text-xs text-gray-400">.ik-shopping-cart</span>
            </div>
            
                  
                  <div class="flex flex-col items-center justify-center gap-2 rounded-lg border border-gray-100 p-4 text-center transition hover:bg-gray-50">
                <i class="ik ik-tv text-3xl text-primary-500"></i>
                <span class="text-sm font-medium text-gray-700">tv</span>
                <span class="text-xs text-gray-400">.ik-tv</span>
            </div>
            
                  
                  <div class="flex flex-col items-center justify-center gap-2 rounded-lg border border-gray-100 p-4 text-center transition hover:bg-gray-50">
                <i class="ik ik-wifi-off text-3xl text-primary-500"></i>
                <span class="text-sm font-medium text-gray-700">wifi-off</span>
                <span class="text-xs text-gray-400">.ik-wifi-off</span>
            </div>
            

                  <div class="flex flex-col items-center justify-center gap-2 rounded-lg border border-gray-100 p-4 text-center transition hover:bg-gray-50">
                <i class="ik ik-gitlab text-3xl text-primary-500"></i>
                <span class="text-sm font-medium text-gray-700">gitlab</span>
                <span class="text-xs text-gray-400">.ik-gitlab</span>
            </div>
            
                  
                  <div class="flex flex-col items-center justify-center gap-2 rounded-lg border border-gray-100 p-4 text-center transition hover:bg-gray-50">
                <i class="ik ik-sliders text-3xl text-primary-500"></i>
                <span class="text-sm font-medium text-gray-700">sliders</span>
                <span class="text-xs text-gray-400">.ik-sliders</span>
            </div>
            

                  <div class="flex flex-col items-center justify-center gap-2 rounded-lg border border-gray-100 p-4 text-center transition hover:bg-gray-50">
                <i class="ik ik-star-on text-3xl text-primary-500"></i>
                <span class="text-sm font-medium text-gray-700">star-on</span>
                <span class="text-xs text-gray-400">.ik-star-on</span>
            </div>
            

                  <div class="flex flex-col items-center justify-center gap-2 rounded-lg border border-gray-100 p-4 text-center transition hover:bg-gray-50">
                <i class="ik ik-heart-on text-3xl text-primary-500"></i>
                <span class="text-sm font-medium text-gray-700">heart-on</span>
                <span class="text-xs text-gray-400">.ik-heart-on</span>
            </div>
            

                  <div class="flex flex-col items-center justify-center gap-2 rounded-lg border border-gray-100 p-4 text-center transition hover:bg-gray-50">
                <i class="ik ik-archive text-3xl text-primary-500"></i>
                <span class="text-sm font-medium text-gray-700">archive</span>
                <span class="text-xs text-gray-400">.ik-archive</span>
            </div>
            

                  <div class="flex flex-col items-center justify-center gap-2 rounded-lg border border-gray-100 p-4 text-center transition hover:bg-gray-50">
                <i class="ik ik-arrow-down-circle text-3xl text-primary-500"></i>
                <span class="text-sm font-medium text-gray-700">arrow-down-circle</span>
                <span class="text-xs text-gray-400">.ik-arrow-down-circle</span>
            </div>
            

                  <div class="flex flex-col items-center justify-center gap-2 rounded-lg border border-gray-100 p-4 text-center transition hover:bg-gray-50">
                <i class="ik ik-arrow-up-circle text-3xl text-primary-500"></i>
                <span class="text-sm font-medium text-gray-700">arrow-up-circle</span>
                <span class="text-xs text-gray-400">.ik-arrow-up-circle</span>
            </div>
            

                  <div class="flex flex-col items-center justify-center gap-2 rounded-lg border border-gray-100 p-4 text-center transition hover:bg-gray-50">
                <i class="ik ik-arrow-left-circle text-3xl text-primary-500"></i>
                <span class="text-sm font-medium text-gray-700">arrow-left-circle</span>
                <span class="text-xs text-gray-400">.ik-arrow-left-circle</span>
            </div>
            

                  <div class="flex flex-col items-center justify-center gap-2 rounded-lg border border-gray-100 p-4 text-center transition hover:bg-gray-50">
                <i class="ik ik-arrow-right-circle text-3xl text-primary-500"></i>
                <span class="text-sm font-medium text-gray-700">arrow-right-circle</span>
                <span class="text-xs text-gray-400">.ik-arrow-right-circle</span>
            </div>
            

                  <div class="flex flex-col items-center justify-center gap-2 rounded-lg border border-gray-100 p-4 text-center transition hover:bg-gray-50">
                <i class="ik ik-bar-chart-line- text-3xl text-primary-500"></i>
                <span class="text-sm font-medium text-gray-700">bar-chart-line-2</span>
                <span class="text-xs text-gray-400">.ik-bar-chart-line-</span>
            </div>
            

                  <div class="flex flex-col items-center justify-center gap-2 rounded-lg border border-gray-100 p-4 text-center transition hover:bg-gray-50">
                <i class="ik ik-bar-chart-line text-3xl text-primary-500"></i>
                <span class="text-sm font-medium text-gray-700">bar-chart-line</span>
                <span class="text-xs text-gray-400">.ik-bar-chart-line</span>
            </div>
            

                  <div class="flex flex-col items-center justify-center gap-2 rounded-lg border border-gray-100 p-4 text-center transition hover:bg-gray-50">
                <i class="ik ik-book-open text-3xl text-primary-500"></i>
                <span class="text-sm font-medium text-gray-700">book-open</span>
                <span class="text-xs text-gray-400">.ik-book-open</span>
            </div>
            

                  <div class="flex flex-col items-center justify-center gap-2 rounded-lg border border-gray-100 p-4 text-center transition hover:bg-gray-50">
                <i class="ik ik-code text-3xl text-primary-500"></i>
                <span class="text-sm font-medium text-gray-700">code</span>
                <span class="text-xs text-gray-400">.ik-code</span>
            </div>
            

                  <div class="flex flex-col items-center justify-center gap-2 rounded-lg border border-gray-100 p-4 text-center transition hover:bg-gray-50">
                <i class="ik ik-database text-3xl text-primary-500"></i>
                <span class="text-sm font-medium text-gray-700">database</span>
                <span class="text-xs text-gray-400">.ik-database</span>
            </div>
            

                  <div class="flex flex-col items-center justify-center gap-2 rounded-lg border border-gray-100 p-4 text-center transition hover:bg-gray-50">
                <i class="ik ik-dollar-sign text-3xl text-primary-500"></i>
                <span class="text-sm font-medium text-gray-700">dollar-sign</span>
                <span class="text-xs text-gray-400">.ik-dollar-sign</span>
            </div>
            

                  <div class="flex flex-col items-center justify-center gap-2 rounded-lg border border-gray-100 p-4 text-center transition hover:bg-gray-50">
                <i class="ik ik-folder-plus text-3xl text-primary-500"></i>
                <span class="text-sm font-medium text-gray-700">folder-plus</span>
                <span class="text-xs text-gray-400">.ik-folder-plus</span>
            </div>
            

                  <div class="flex flex-col items-center justify-center gap-2 rounded-lg border border-gray-100 p-4 text-center transition hover:bg-gray-50">
                <i class="ik ik-gift text-3xl text-primary-500"></i>
                <span class="text-sm font-medium text-gray-700">gift</span>
                <span class="text-xs text-gray-400">.ik-gift</span>
            </div>
            

                  <div class="flex flex-col items-center justify-center gap-2 rounded-lg border border-gray-100 p-4 text-center transition hover:bg-gray-50">
                <i class="ik ik-folder-minus text-3xl text-primary-500"></i>
                <span class="text-sm font-medium text-gray-700">folder-minus</span>
                <span class="text-xs text-gray-400">.ik-folder-minus</span>
            </div>
            

                  <div class="flex flex-col items-center justify-center gap-2 rounded-lg border border-gray-100 p-4 text-center transition hover:bg-gray-50">
                <i class="ik ik-git-commit text-3xl text-primary-500"></i>
                <span class="text-sm font-medium text-gray-700">git-commit</span>
                <span class="text-xs text-gray-400">.ik-git-commit</span>
            </div>
            

                  <div class="flex flex-col items-center justify-center gap-2 rounded-lg border border-gray-100 p-4 text-center transition hover:bg-gray-50">
                <i class="ik ik-git-branch text-3xl text-primary-500"></i>
                <span class="text-sm font-medium text-gray-700">git-branch</span>
                <span class="text-xs text-gray-400">.ik-git-branch</span>
            </div>
            

                  <div class="flex flex-col items-center justify-center gap-2 rounded-lg border border-gray-100 p-4 text-center transition hover:bg-gray-50">
                <i class="ik ik-git-pull-request text-3xl text-primary-500"></i>
                <span class="text-sm font-medium text-gray-700">git-pull-request</span>
                <span class="text-xs text-gray-400">.ik-git-pull-request</span>
            </div>
            

                  <div class="flex flex-col items-center justify-center gap-2 rounded-lg border border-gray-100 p-4 text-center transition hover:bg-gray-50">
                <i class="ik ik-git-merge text-3xl text-primary-500"></i>
                <span class="text-sm font-medium text-gray-700">git-merge</span>
                <span class="text-xs text-gray-400">.ik-git-merge</span>
            </div>
            

                  <div class="flex flex-col items-center justify-center gap-2 rounded-lg border border-gray-100 p-4 text-center transition hover:bg-gray-50">
                <i class="ik ik-linkedin text-3xl text-primary-500"></i>
                <span class="text-sm font-medium text-gray-700">linkedin</span>
                <span class="text-xs text-gray-400">.ik-linkedin</span>
            </div>
            

                  <div class="flex flex-col items-center justify-center gap-2 rounded-lg border border-gray-100 p-4 text-center transition hover:bg-gray-50">
                <i class="ik ik-hard-drive text-3xl text-primary-500"></i>
                <span class="text-sm font-medium text-gray-700">hard-drive</span>
                <span class="text-xs text-gray-400">.ik-hard-drive</span>
            </div>
            

                  <div class="flex flex-col items-center justify-center gap-2 rounded-lg border border-gray-100 p-4 text-center transition hover:bg-gray-50">
                <i class="ik ik-more-vertical- text-3xl text-primary-500"></i>
                <span class="text-sm font-medium text-gray-700">more-vertical-2</span>
                <span class="text-xs text-gray-400">.ik-more-vertical-</span>
            </div>
            

                  <div class="flex flex-col items-center justify-center gap-2 rounded-lg border border-gray-100 p-4 text-center transition hover:bg-gray-50">
                <i class="ik ik-more-horizontal- text-3xl text-primary-500"></i>
                <span class="text-sm font-medium text-gray-700">more-horizontal-2</span>
                <span class="text-xs text-gray-400">.ik-more-horizontal-</span>
            </div>
            

                  <div class="flex flex-col items-center justify-center gap-2 rounded-lg border border-gray-100 p-4 text-center transition hover:bg-gray-50">
                <i class="ik ik-rss text-3xl text-primary-500"></i>
                <span class="text-sm font-medium text-gray-700">rss</span>
                <span class="text-xs text-gray-400">.ik-rss</span>
            </div>
            

                  <div class="flex flex-col items-center justify-center gap-2 rounded-lg border border-gray-100 p-4 text-center transition hover:bg-gray-50">
                <i class="ik ik-send text-3xl text-primary-500"></i>
                <span class="text-sm font-medium text-gray-700">send</span>
                <span class="text-xs text-gray-400">.ik-send</span>
            </div>
            

                  <div class="flex flex-col items-center justify-center gap-2 rounded-lg border border-gray-100 p-4 text-center transition hover:bg-gray-50">
                <i class="ik ik-shield-off text-3xl text-primary-500"></i>
                <span class="text-sm font-medium text-gray-700">shield-off</span>
                <span class="text-xs text-gray-400">.ik-shield-off</span>
            </div>
            

                  <div class="flex flex-col items-center justify-center gap-2 rounded-lg border border-gray-100 p-4 text-center transition hover:bg-gray-50">
                <i class="ik ik-shopping-bag text-3xl text-primary-500"></i>
                <span class="text-sm font-medium text-gray-700">shopping-bag</span>
                <span class="text-xs text-gray-400">.ik-shopping-bag</span>
            </div>
            

                  <div class="flex flex-col items-center justify-center gap-2 rounded-lg border border-gray-100 p-4 text-center transition hover:bg-gray-50">
                <i class="ik ik-terminal text-3xl text-primary-500"></i>
                <span class="text-sm font-medium text-gray-700">terminal</span>
                <span class="text-xs text-gray-400">.ik-terminal</span>
            </div>
            

                  <div class="flex flex-col items-center justify-center gap-2 rounded-lg border border-gray-100 p-4 text-center transition hover:bg-gray-50">
                <i class="ik ik-truck text-3xl text-primary-500"></i>
                <span class="text-sm font-medium text-gray-700">truck</span>
                <span class="text-xs text-gray-400">.ik-truck</span>
            </div>
            

                  <div class="flex flex-col items-center justify-center gap-2 rounded-lg border border-gray-100 p-4 text-center transition hover:bg-gray-50">
                <i class="ik ik-zap-off text-3xl text-primary-500"></i>
                <span class="text-sm font-medium text-gray-700">zap-off</span>
                <span class="text-xs text-gray-400">.ik-zap-off</span>
            </div>
            

                  <div class="flex flex-col items-center justify-center gap-2 rounded-lg border border-gray-100 p-4 text-center transition hover:bg-gray-50">
                <i class="ik ik-youtube text-3xl text-primary-500"></i>
                <span class="text-sm font-medium text-gray-700">youtube</span>
                <span class="text-xs text-gray-400">.ik-youtube</span>
            </div>
        </div>
    </x-card>
@endsection
