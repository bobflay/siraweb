
<template>
  <div class="mb-8">
    <div class="flex items-center mb-3">
            <h1 class="flex-auto truncate text-90 font-normal text-2xl">Elevator Monitor</h1>
    </div>
    <div class="card mb-6 py-3 px-6">
      <div class="flex border-b border-40 -mx-6 px-6">
            <div class="w-1/3 py-4 border-r border-40">
                <div class="flex">
                    <div class="w-1/2 ">
                        Current Floor
                    </div>
                    <div class="w-1/5 text-center">
                      <span>{{current_floor}}</span>
                    </div>
                    <div class="w-1/5 text-center">
                        {{direction}}  
                    </div>
                    <div class="w-1/2 text-center">
                        {{doors}}
                    </div>
                </div>
            </div>
            <div class="w-1/3 py-4 border-r border-40">
                <div class="flex">
                    <div class="w-1/2 text-center">
                        Destination Floor
                    </div>
                    <div class="w-1/2 text-center">
                        {{destination_floor}}
                    </div>
                </div>
            </div>
            <div class="w-1/3 py-4 ">
                <div class="flex">
                    <div class="w-1/2 text-center">
                        Mode Of Operation
                    </div>
                    <div class="w-1/2 text-center">
                        {{mode_of_operation}}
                    </div>
                </div>
            </div>


      </div>
      <div class="flex border-b border-40 -mx-6 px-6">
            <div class="w-1/2 py-4 border-r border-40">
                <div class="flex">
                    <div class="w-1/2 text-center">
                        Speed
                    </div>
                    <div class="w-1/2">
                        {{speed}}
                    </div>
                </div>
            </div>
            <div class="w-1/2 py-4 ">
                <div class="flex">
                    <div class="w-1/2 text-center">
                        Position
                    </div>
                    <div class="w-1/2">
                        {{position}}
                    </div>
                </div>
            </div>
      </div>
    </div>

  </div>
  
</template>

<script>
export default {
  props: ['resourceName', 'resourceId', 'panel'],
  data() {
      return {
        packets:[],
        current_floor:0,
        destination_floor:0,
        mode_of_operation:"Offline",
        doors:"",
        speed:0,
        position:0,
        direction:"",
      }
    },
    created(){

        var channel = this.$pusher.subscribe('elevator');
        var that = this;
        channel.bind('App\\Events\\PacketReceived', function(data) {
        that.packets.push(data.elevator);
        that.current_floor = data.elevator.current_floor;
        that.destination_floor = data.elevator.destination_floor;
        if(that.destination_floor > that.current_floor)
        {
            that.direction = "⬆"
        }else if (that.destination_floor < that.current_floor){
            that.direction = "⬇"
        }else{
            if(that.speed == 0)
            {
                that.direction = ""
            }
        }
        if(data.elevator.mode_of_operation == 1)
        {
            that.mode_of_operation = "Normal";
        }else{
            that.mode_of_operation = "Offline";
        }
        console.log(data.elevator.door);
        switch (data.elevator.door) {
            case "0":
                that.doors = "[|]"
                break;
            case "1":
                that.doors = "[<>]"
                break;
            case "2":
                that.doors = "[  ]"
                break;
            case "3":
                that.doors = "[><]"
                break;
        
            default:
                break;
        }

        that.speed = data.elevator.speed;
        that.position = data.elevator.position

        console.log(data.elevator);
        });
    }

}
</script>
