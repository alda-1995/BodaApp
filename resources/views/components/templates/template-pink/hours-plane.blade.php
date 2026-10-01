<div class="bg-primary300 overflow-hidden relative" id="planeHoursBlock">
    <div class="avion-plane absolute top-[30%] md:top-[10%] -left-[50%] md:left-[20%] z-[5]">
        <img class="max-w-full" src="{{ asset('images/avion.png') }}" alt="Avion">
    </div>
    <div class="absolute top-0 left-0 h-full w-full z-[1] bg-gradient-plane">
    </div>
    <div class="nube1 absolute top-[30%] left-[-30%] md:top-0 md:left-0 z-[2] mix-blend-screen">
        <img class="max-w-full" src="{{ asset('images/nube1.png') }}" alt="nube img">
    </div>
    <div class="nubecenter absolute top-[48%] md:top-0 left-1/2 -translate-x-1/2 z-[2] mix-blend-screen">
        <img class="max-w-full" src="{{ asset('images/nubecenter.png') }}" alt="nube img">
    </div>
    <div class="nube3 absolute top-[30%] md:top-0 -right-[65%] md:right-0 z-[2] mix-blend-screen">
        <img class="max-w-full" src="{{ asset('images/nube3.png') }}" alt="nube img">
    </div>
    <div class="relative min-h-screen z-[2] flex justify-center items-end">
        <svg id="path-svg" class="hidden md:block w-full h-[300px] lg:h-[400px]" viewBox="0 0 1280 400" fill="none"
            xmlns="http://www.w3.org/2000/svg" preserveAspectRatio="none">
            <path d="M1280 400C1280 180.191 993.462 2 640 2C286.538 2 0 180.191 0 400" stroke="#725872" stroke-width="3"
                stroke-dasharray="10 10" />
        </svg>
        <svg id="path-svg-mobile" class="md:hidden" xmlns="http://www.w3.org/2000/svg" width="375" height="155" viewBox="0 0 375 155" fill="none">
            <path d="M376 155C376 70.5004 291.83 2 188 2C84.1705 2 0 70.5004 0 155" stroke="#725872" stroke-width="3" stroke-dasharray="10 10"/>
        </svg>
        <div id="cursor-progress">
            <svg xmlns="http://www.w3.org/2000/svg" width="25" height="22" viewBox="0 0 25 22" fill="none">
                <path
                    d="M23.2041 12.7756L4.7621 21.7045C3.79262 22.1786 2.66827 21.9925 1.88875 21.242C1.81879 21.17 1.74882 21.0981 1.69057 21.0274C1.08169 20.2875 0.936388 19.2848 1.33131 18.3879L4.52596 11.2161C4.63311 10.9661 4.63057 10.6876 4.49975 10.45L0.987643 3.88756C0.477219 2.9264 0.617594 1.78575 1.34695 0.980516C2.07632 0.175283 3.1959 -0.077052 4.1972 0.339844L23.0736 8.14598C24.0068 8.5326 24.6085 9.40789 24.6406 10.4172C24.6663 11.4319 24.1168 12.3374 23.2041 12.7756Z"
                    fill="#725872" />
            </svg>
        </div>
    </div>
    <div class="text-change-animated absolute bottom-[2%] md:bottom-[10%] left-1/2 -translate-x-1/2 z-[3] opacity-100">
        <div class="container">
            <div class="max-w-lg mx-auto">
                <div class="flex flex-col items-center text-center">
                    <h3 class="font-main text-size-subtitle text-black mb-2 md:mb-8">4:00 PM</h3>
                    <p class="font-secondary text-complement400">Ceremonia religiosa</p>
                </div>
            </div>
        </div>
    </div>
    <div class="text-change-animated absolute bottom-[2%] md:bottom-[10%] left-1/2 -translate-x-1/2 z-[3] opacity-0">
        <div class="container">
            <div class="max-w-lg mx-auto">
                <div class="flex flex-col items-center text-center">
                    <h3 class="font-main text-size-subtitle text-black mb-2 md:mb-8">5:00 PM</h3>
                    <p class="font-secondary text-complement400">Comida</p>
                </div>
            </div>
        </div>
    </div>
    <div class="text-change-animated absolute bottom-[2%] md:bottom-[10%] left-1/2 -translate-x-1/2 z-[3] opacity-0">
        <div class="container">
            <div class="max-w-lg mx-auto">
                <div class="flex flex-col items-center text-center">
                    <h3 class="font-main text-size-subtitle text-black mb-2 md:mb-8">6:00 PM</h3>
                    <p class="font-secondary text-complement400">Se parte el pastel</p>
                </div>
            </div>
        </div>
    </div>
    <div class="text-change-animated absolute bottom-[2%] md:bottom-[10%] left-1/2 -translate-x-1/2 z-[3] opacity-0">
        <div class="container">
            <div class="max-w-lg mx-auto">
                <div class="flex flex-col items-center text-center">
                    <h3 class="font-main text-size-subtitle text-black mb-2 md:mb-8">7:00 PM</h3>
                    <p class="font-secondary text-complement400">Baile de los novios</p>
                </div>
            </div>
        </div>
    </div>
</div>